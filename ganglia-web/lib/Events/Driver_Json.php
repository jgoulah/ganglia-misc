<?php

$conf['ganglia_dir'] = dirname(dirname(dirname(__FILE__)));

include_once $conf['ganglia_dir'] . "/eval_conf.php";
include_once $conf['ganglia_dir'] . "/lib/common_api.php";

//////////////////////////////////////////////////////////////////////////////
// Add an event to  the JSON
//////////////////////////////////////////////////////////////////////////////
function ganglia_events_add( $event ) {
	global $conf;
	$events_array = ganglia_events_get();
	$events_array[] = $event;
	$json = json_encode($events_array);
	if ( file_put_contents($conf['overlay_events_file'], $json) === FALSE ) {
		api_return_error( "Can't write to " . $conf['overlay_events_file'] . ". Please check permissions." );
	} else {
		$message = array( "status" => "ok", "event_id" => $event['event_id']);
	}
	return $message;
} // end method ganglia_events_add

//////////////////////////////////////////////////////////////////////////////
// Gets a list of all events
//////////////////////////////////////////////////////////////////////////////
function ganglia_events_get() {
	global $conf;
	$events_json = file_get_contents($conf['overlay_events_file']);
	$events_array = json_decode($events_json, TRUE);
	return $events_array;
} // end method ganglia_events_get



function ganglia_event_modify( $event ) {
	global $conf;
  $event_found = 0;
  $events_array = ganglia_events_get();
  $new_events_array = array();

  if (isset($event['id'])) {
    api_return_error( "Event ID not found" );
  } // isset event_id

  foreach ( $events_array AS $k => $e ) {
    if ( $e['event_id'] == $event['id'] ) {
      $event_found = 1;

      if (isset( $event['start_time'] )) {
        if ( $event['start_time'] == "now" ) {
          $e['start_time'] = time();
        } else if ( is_numeric($event['start_time']) ) {
          $e['start_time'] = $event['start_time'];
        } else {
          $e['start_time'] = strtotime($event['start_time']);
        }
      } // end isset start_time

      foreach(array('cluster', 'description', 'summary', 'grid', 'host_regex') AS $k) {
        if (isset( $event[$k] )) {
          $e[$k] = $event[$k];
        }
      } // end foreach

      if ( isset($event['end_time']) ) {
        $e['end_time'] = $event['end_time'] == "now" ? time() : strtotime($event['end_time']);
      } // end isset end_time
    } // if event_id

    // Add either original or modified event back in
    $new_events_array[] = $e;
  } // foreach events array
  if ( $event_found == 1 ) {
    $json = json_encode($new_events_array);
    if ( file_put_contents($conf['overlay_events_file'], $json) === FALSE ) {
      api_return_error( "Can't write to file " . $conf['overlay_events_file'] . ". Perhaps permissions are wrong." );
    } else {
      $message = array( "status" => "ok", "message" => "Event ID " . $event_id . " removed successfully" );
    }
  } // end if event_found

  return $message;
} // end method ganglia_event_modify

?>
