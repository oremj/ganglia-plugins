<?php

/* Pass in by reference! */
function graph_apache_report ( &$rrdtool_graph ) {

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

    $title = 'Apache';
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
          "DEF:'num_nodes'='${rrd_dir}/apache_avg_res_time.rrd':'num':AVERAGE "
        . "DEF:'avg_res'='${rrd_dir}/apache_avg_res_time.rrd':'sum':AVERAGE "
        . "CDEF:'aavg_res'=avg_res,num_nodes,/ "
        . "LINE2:'aavg_res'#3366FF:'Response Time' ";
    $rrdtool_graph['series'] = $series;

    return $rrdtool_graph;

}

?>
