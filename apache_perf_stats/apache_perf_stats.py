from glob import glob
import os
import re
import time
import urllib2


def newest_file(file_glob):
    files = ((os.stat(f).st_mtime, f) for f in glob(file_glob))
    f = max(files)[1]
    return f


def follower(file_glob):
    filename = newest_file(file_glob)
    file = open(filename)
    file.seek(0, 2)

    while True:
        line = file.readline()

        if line:
            yield line
        else:
            tmp_filename = newest_file(file_glob)
            if tmp_filename == filename:
                yield None
            else:
                file.close()

                filename = tmp_filename
                file = open(filename)


def apache_stats(log_glob):

    regex = re.compile('(?P<time>\d+)$')
    apache_follower = follower(log_glob)

    times = []

    while True:
        line = apache_follower.next()

        if line:
            match = regex.search(line)

            if match:
                time = match.group('time')
                times.append(float(time))
        else:
            cnt = len(times)
            if cnt > 0:
                yield sum(times) / cnt
            else:
                yield 0.0

            times = []


def apache_server_status():
    interesting = {'Total Accesses': int}
    status = None

    try:
        status = urllib2.urlopen('http://localhost/server-status?auto')
    except urllib2.URLError:
        return 0

    stats = {}
    for line in status:
        key, value = line.strip().split(': ')
        if key in interesting:
            stats[key] = interesting[key](value)

    return stats


class Metric(object):

    def __init__(self, name, description, units="Items"):
        self.name = name
        self.description = description
        self.units = units

    def create_descriptor(self, params):
        self.params = params
        return {
            'name': 'apache_%s' % self.name,
            'call_back': self.callback,
            'time_max': 20,
            'value_type': 'float',
            'units': self.units,
            'slope': 'both',
            'format': '%0.2f',
            'description': self.description,
            'groups': 'apachestats',
        }


class ServerStatusMetric(Metric):

    def __init__(self, *args, **kwargs):
        super(ServerStatusMetric, self).__init__(*args, **kwargs)
        self.prev = None
        self.prev_time = None

    def callback(self, name):
        stats = apache_server_status() 
        total_accesses = stats['Total Accesses']

        r = 0.0
        now = time.time()

        if self.prev and self.prev <= total_accesses:
            r = (total_accesses - self.prev) / (now - self.prev_time)

        self.prev = total_accesses
        self.prev_time = now

        return r


class LogMetric(Metric):

    def __init__(self, *args, **kwargs):
        super(LogMetric, self).__init__(*args, **kwargs)
        self.stats = None

    def callback(self, name):
        if not self.stats:
            self.stats = apache_stats(self.params['PerfLogGlob'])

        return self.stats.next()


METRICS = [
    LogMetric("avg_res_time", "Average Response Time", "Seconds"),
    ServerStatusMetric("req_per_sec", "Requests per second", "Requests"),
]


def metric_init(params):
    return [m.create_descriptor(params) for m in METRICS]


def metric_cleanup():
    pass


if __name__ == "__main__":
    metrics = metric_init({'PerfLogGlob': '/var/log/ganglia/perf_*'})
    for m in metrics:
        print m['call_back'](m['name'])
    time.sleep(30)
    for m in metrics:
        print m['call_back'](m['name'])
