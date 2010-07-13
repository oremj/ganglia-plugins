<?php

/* Pass in by reference! */
function graph_apache_server_report ( &$rrdtool_graph ) {

    global $context,
           $hostname,
           $mem_shared_color,
           $mem_cached_color,
           $mem_buffered_color,
           $mem_swapped_color,
           $mem_used_color,
           $cpu_num_color,
           $range,
           $rrd_dir,
           $size,
           $strip_domainname;

    if ($strip_domainname) {
       $hostname = strip_domainname($hostname);
    }

    $title = 'Apache Server Status';
    if ($context != 'host') {
       $rrdtool_graph['title'] = $title;
    } else {
       $rrdtool_graph['title'] = "$hostname $title last $range";
    }
    $rrdtool_graph['height']        += $size == 'medium' ? 28 : 0 ;   // Fudge to account for number of lines in the chart legend
    $rrdtool_graph['lower-limit']    = '0';
    $rrdtool_graph['vertical-label'] = 'per second';
    $rrdtool_graph['extras']         = '--rigid';

    $series = 
        "DEF:'req_sec'='${rrd_dir}/apache_req_per_sec.rrd':'sum':AVERAGE "
        . "LINE2:'req_sec'#3366FF:'Req/s' ";
    $rrdtool_graph['series'] = $series;

    return $rrdtool_graph;

}

?>
