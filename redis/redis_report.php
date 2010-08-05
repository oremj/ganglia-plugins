<?php

/* Pass in by reference! */
function graph_redis_report ( &$rrdtool_graph ) {

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

    $title = 'Redis Status';
    if ($context != 'host') {
       $rrdtool_graph['title'] = $title;
    } else {
       $rrdtool_graph['title'] = "$hostname $title last $range";
    }
    $rrdtool_graph['height']        += $size == 'medium' ? 28 : 0 ;   // Fudge to account for number of lines in the chart legend
    $rrdtool_graph['lower-limit']    = '0';
    $rrdtool_graph['vertical-label'] = 'count';
    $rrdtool_graph['extras']         = '--rigid';

    $series = 
        "DEF:'processed'='${rrd_dir}/redis_amo_prod_total_commands_processed.rrd':'sum':AVERAGE "
        . "DEF:'received'='${rrd_dir}/redis_amo_prod_total_connections_received.rrd':'sum':AVERAGE "
        . "DEF:'clients'='${rrd_dir}/redis_amo_prod_connected_clients.rrd':'sum':AVERAGE "
        . "LINE2:'processed'#0E8290:'Cmds Processed / min' "
        . "LINE2:'clients'#EE02F0:'Clients Connected / min' "
        . "LINE2:'received'#00EEBB:'Cmds Received' ";
    $rrdtool_graph['series'] = $series;

    return $rrdtool_graph;

}

?>
