<?php

/* Pass in by reference! */
function graph_memcached_report ( &$rrdtool_graph ) {

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

    $title = 'Memcache';
    if ($context != 'host') {
       $rrdtool_graph['title'] = $title;
    } else {
       $rrdtool_graph['title'] = "$hostname $title last $range";
    }
    $rrdtool_graph['height']        += $size == 'medium' ? 28 : 0 ;   // Fudge to account for number of lines in the chart legend
    $rrdtool_graph['lower-limit']    = '0';
    $rrdtool_graph['vertical-label'] = 'per second';
    $rrdtool_graph['extras']         = '--rigid';

    $series = "DEF:'hits'='${rrd_dir}/memcache_get_hits.rrd':'sum':AVERAGE "
        ."DEF:'misses'='${rrd_dir}/memcache_get_misses.rrd':'sum':AVERAGE "
        ."DEF:'sets'='${rrd_dir}/memcache_cmd_set.rrd':'sum':AVERAGE "
        ."DEF:'gets'='${rrd_dir}/memcache_cmd_get.rrd':'sum':AVERAGE "
        ."LINE2:'gets'#0E8290:'Gets' "
        ."LINE2:'sets'#DEA840:'Sets' "
        ."LINE2:'hits'#2EB800:'Hits' "
        ."LINE2:'misses'#3366FF:'Misses' ";
    $rrdtool_graph['series'] = $series;

    return $rrdtool_graph;

}

?>
