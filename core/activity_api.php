<?php

/**
 * Get latest bug notes for period
 * @param int    $p_project_id Project id
 * @param string $p_date_from  Start date
 * @param string $p_date_to    End date
 * @param int    $p_limit      Bug notes limit
 * @return array
 */
function activity_get_latest_bugnotes( $p_project_id, $p_date_from, $p_date_to, $p_limit = 500 ) {
    $c_from = strtotime ( $p_date_from );
    $c_to   = strtotime ( $p_date_to ) + SECONDS_PER_DAY - 1;
    if ($c_to === false || $c_from === false) {
        error_parameters ( array($p_date_from, $p_date_to) );
        trigger_error ( ERROR_GENERIC, ERROR );
    }
    $t_bug_table          = db_get_table ( 'mantis_bug_table' );
    $t_bugnote_table      = db_get_table ( 'mantis_bugnote_table' );
    $t_bugnote_text_table = db_get_table ( 'mantis_bugnote_text_table' );

    $t_query    = "SELECT b.*, t.note
                    FROM      $t_bugnote_table b
                    LEFT JOIN $t_bug_table bt ON b.bug_id = bt.id
                    LEFT JOIN $t_bugnote_text_table t ON b.bugnote_text_id = t.id
                    WHERE bt.project_id=" . db_param () . " AND b.date_submitted >= $c_from AND b.date_submitted <= $c_to" . ' ORDER BY b.id DESC LIMIT ' . $p_limit;
    $t_bugnotes = array();

    $t_result = db_query_bound ( $t_query, array($p_project_id) );

    while ($row = db_fetch_array ( $t_result )) {
        $t_bugnote                  = new BugnoteData();
        $t_bugnote->id              = $row['id'];
        $t_bugnote->bug_id          = $row['bug_id'];
        $t_bugnote->bugnote_text_id = $row['bugnote_text_id'];
        $t_bugnote->note            = $row['note'];
        $t_bugnote->view_state      = $row['view_state'];
        $t_bugnote->reporter_id     = $row['reporter_id'];
        $t_bugnote->date_submitted  = $row['date_submitted'];
        $t_bugnote->last_modified   = $row['last_modified'];
        $t_bugnote->note_type       = $row['note_type'];
        $t_bugnote->note_attr       = $row['note_attr'];
        $t_bugnote->time_tracking   = $row['time_tracking'];
        $t_bugnotes[]               = $t_bugnote;
    }
    return $t_bugnotes;
}