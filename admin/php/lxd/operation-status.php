<?php

if (!empty($_SERVER['PHP_AUTH_USER'])) {

  //Instantiate the GET variables
  if (isset($_GET['remote']))
    $remote = filter_var(urldecode($_GET['remote']), FILTER_SANITIZE_STRING);
  if (isset($_GET['id']))
    $id = filter_var(urldecode($_GET['id']), FILTER_SANITIZE_STRING);

  $cert = "/var/lxdware/data/lxd/client.crt";
  $key = "/var/lxdware/data/lxd/client.key";

  $db = new SQLite3('/var/lxdware/data/sqlite/lxdware.sqlite');
  $db_statement = $db->prepare('SELECT * FROM lxd_hosts WHERE id = :id LIMIT 1;');
  $db_statement->bindValue(':id', $remote);
  $db_results = $db_statement->execute();

  while($row = $db_results->fetchArray()){
    $url = "https://" . $row['host'] . ":" . $row['port'] . "/1.0/operations?recursion=1";
    $operations_api_data = shell_exec("sudo curl -k -L --cert $cert --key $key -X GET $url");
    $operations_api_data= json_decode($operations_api_data, true);
    $operations_data = $operations_api_data['metadata'];

    $results = "";

    if (!empty($operations_data)){

      if (!empty($operations_data['running'])){
        foreach ($operations_data['running'] as $running_task){
          $results .=  $running_task['description'];
          if ($running_task['description'] == "Downloading image"){
            $results .= " " . $running_task['metadata']['download_progress'];
          }
        }
      }

      if (!empty($operations_data['failure'])){
        foreach ($operations_data['failure'] as $failed_task){
          $results .=  $failed_task['description'] . " Error: " . $failed_task['err'];
        }
      }
    
    }
  
  }
  echo $results;
}

?>