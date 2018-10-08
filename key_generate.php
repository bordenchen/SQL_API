<?php
	//$key previously generated safely, ie: openssl_random_pseudo_bytes
	$plaintext = "sa=B40545A9101649915B6A3A103FB1F2EDE9C0B620";
	$ivlen = openssl_cipher_iv_length($cipher="AES-256-OFB");
	$iv = openssl_random_pseudo_bytes($ivlen);
	$key = "k39b";
	$ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$hmac = hash_hmac('BF-CBC', $ciphertext_raw, $key, $as_binary=true);
	$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
	echo $ciphertext;
	echo "<br/>";
	//decrypt later....
	$c = base64_decode($ciphertext);
	$ivlen = openssl_cipher_iv_length($cipher="AES-256-OFB");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=0);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$calcmac = hash_hmac('BF-CBC', $ciphertext_raw, $key, $as_binary=true);
	echo $original_plaintext."\n";
	if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
	{
		echo $original_plaintext."\n";
	}
?>