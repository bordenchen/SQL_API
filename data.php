<?php
if (isset($_GET["key"])){
	$ciphertext = htmlspecialchars($_GET["key"]);
	
	$password = "k39b";
	//decrypt 
	$c = base64_decode($ciphertext);
	$ivlen = openssl_cipher_iv_length($cipher="AES-256-OFB");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=0);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $password, $options=OPENSSL_RAW_DATA, $iv);
	$calcmac = hash_hmac('BF-CBC', $ciphertext_raw, $password, $as_binary=true);

	$user_info =  explode("=",$original_plaintext);
	$serverName = "DESKTOP-LHT5MBF"; //serverName\instanceName
	$connectionInfo = array( "Database"=>"ApiCtrl", "UID"=>"JSClient", "PWD"=>"jsclient");
	$conn = sqlsrv_connect( $serverName, $connectionInfo);
	if( $conn ) {
		$sql_usr = "SELECT * FROM [dbo].[CtrlUser] WHERE [J_Admin]='$user_info[0]'";
		$result_usr = sqlsrv_query($conn, $sql_usr) or die(sqlsrv_errors());
		while($row = sqlsrv_fetch_array($result_usr, SQLSRV_FETCH_ASSOC)) {
			if ($row[J_Paswd] = $user_info[1]){
				if(isset($_GET["station"])){
					$STid = $_GET["station"];
					$sql_dt = "SELECT [dTable] FROM [dbo].[CtrlStation] WHERE [STid]='$STid'";
					$result_dt = sqlsrv_query($conn, $sql_dt);
					if( sqlsrv_fetch( $result_dt ) == false) {
						echo "Wrong Station ID";
						die( print_r( sqlsrv_errors(), true));
					}
					$station_table = sqlsrv_get_field( $result_dt, 0);	
					$table_type = array();
					$sql = "SELECT [TableType] FROM [dbo].[CtrlStationTables] WHERE ([STid]='$STid' AND [TableType] != 'S8')";
					$stmt = sqlsrv_query($conn, $sql);
					while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
						$table_type[]=$row[TableType];
					}
					sqlsrv_free_stmt($stmt);
				}else{
					echo "--Please Select  an Avalible Station ID--";
					echo "<br/>";
					$sql_st = "SELECT [STid] FROM [dbo].[CtrlAccess] WHERE [GID]='$row[GID]'";
					$result_st = sqlsrv_query($conn, $sql_st) or die(sqlsrv_errors());
					while($ST = sqlsrv_fetch_array($result_st, SQLSRV_FETCH_ASSOC)) {
						print_r($ST[STid]);
						echo "<br/>";
					}
					sqlsrv_free_stmt($result_st);
				}
			}else{
				echo "Wrong Key!!";
			}
				
		}
		sqlsrv_free_stmt($result_usr);
		sqlsrv_close($conn);
	}else{
		 echo "Connection could not be established.<br />";
		 #die( print_r( sqlsrv_errors(), true));
		 die();
	}
	if(isset($station_table)){
		$connectionInfo = array( "Database"=>"ApiDC", "UID"=>"JSClient", "PWD"=>"jsclient");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);
		if( $conn ) {
			if(isset($_GET['interval'])){
				$interval = $_GET['interval'];
				$zfill_interval = "T".sprintf("%02d", $interval);
				if (in_array("$zfill_interval", $table_type)){
					if(isset($_GET['row'])){
						$select_row = $_GET['row'];
						$table_name = $station_table.$zfill_interval;
						$sql = "SELECT TOP ($select_row)* FROM [dbo].[$table_name] WHERE [STID]='$STid'";
						$result = sqlsrv_query($conn, $sql) or die(sqlsrv_errors());
						$data = array();
						while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$data[]=$row;
						}
						echo json_encode($data); 
						sqlsrv_free_stmt($result);
					}else{
						$table_name = $station_table.$zfill_interval;
						$sql = "SELECT TOP (1000) * FROM [dbo].[$table_name] WHERE [STID]='$STid'";
						$result = sqlsrv_query($conn, $sql) or die(sqlsrv_errors());
						$data = array();
						while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$data[]=$row;
						}
						echo $data;
						echo json_encode($data); 		
						sqlsrv_free_stmt($result);
					}
				}else{
					echo "Specified Interval Not Avalible, Please Enter An Avalible Interval. <br />";
					echo "--Avalible Interval-- <br />";
					foreach($table_type as $table){
						echo "$table <br />";
					}						
				}
			}else{
				$table_name = $station_table.'T60';
				$sql = "SELECT * FROM [dbo].[$table_name] WHERE [STID]='$STid'";
				$result = sqlsrv_query($conn, $sql) or die(sqlsrv_errors());
				$data = array();
				while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$data[]=$row;		
				}
				echo json_encode($data); 	
				sqlsrv_free_stmt($result);
			}
			sqlsrv_close($conn);
		}else{
			 echo "Connection could not be established.<br />";
			 die();
			 #die( print_r( sqlsrv_errors(), true));
		}
	}
}else{
	echo "Missing Key!!";
}
?>