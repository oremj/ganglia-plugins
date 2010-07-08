import socket
import time

class MemcacheStats:

    CACHE_TIME = 4

    def __init__(self):
        self.cache = ()

    def get_stats(self):
        if self.cache and time.time() - self.cache[1] < self.CACHE_TIME:
            return self.cache

        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

        s.connect(('localhost', 11211))
        s.sendall("stats\r\n")
        buffer = []
        while True:
            l = s.recv(1096)
            buffer.append(l)
            if 'END' in l:
                break
        s.close()

        data = {}
        for l in "".join(buffer).split("\r\n"):
            try:
                meth, type, val = l.split()
                data[type] = int(val)
            except ValueError:
                pass

        self.cache = (data, time.time())
        return self.cache

memcache_stats = MemcacheStats()

class Metric(object):
    def __init__(self, name, description, units="Items"):
        self.name = name
        self.description = description
        self.units = units

    def create_descriptor(self):
        return {
            'name': 'memcache_%s' % self.name,
            'call_back': self.callback,
            'time_max': 20,
            'value_type': 'uint',
            'units': self.units,
            'slope': 'both',
            'format': '%u',
            'description': self.description,
            'groups': 'memcache',
        }

    def callback(self, name):
        v = memcache_stats.get_stats()[0][self.name]
        return v
            

class CounterMetric(Metric):

    def __init__(self, *args, **kwargs):
        super(CounterMetric, self).__init__(*args, **kwargs)
        self.previous = None

    def callback(self, name):
        v = None
        s = memcache_stats.get_stats()
        if self.previous:
            time_diff = int(s[1] - self.previous[1])
            if time_diff > 0 and s[0][self.name] - self.previous[0][self.name] > 0:
                v = (s[0][self.name] - self.previous[0][self.name]) / time_diff
            
        self.previous = s
            
        return v

class GaugeMetric(Metric):
    pass

METRICS = [
    GaugeMetric('curr_connections', 'Current connections', "Connections"),
    GaugeMetric('curr_items', 'Current items'),
    CounterMetric('cmd_get', "gets", "Commands"),
    CounterMetric('cmd_set', "sets", "Commands"),
    CounterMetric('get_hits', "hits", "Hits"),
    CounterMetric('get_misses', "misses", "Misses"),
    CounterMetric('bytes_read', "bytes read", "Bytes"),
    CounterMetric('bytes_written', "bytes written", "Bytes"),
    CounterMetric('total_items', "Total items"),
]

def metric_init(params):
    return [m.create_descriptor() for m in METRICS]

def metric_cleanup():
    pass

if __name__ == '__main__':
    for i in metric_init([]):
        print i['call_back']('test')
    time.sleep(4)
    for i in metric_init([]):
        print i['call_back']('test')
