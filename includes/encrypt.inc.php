<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage encrypt
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

function encrypt_sodium($string, $key, $nonce = NULL) {
  // https://dev.to/paragonie/libsodium-quick-reference#crypto-secretbox

  $key_len = check_extension_exists('mbstring') ? mb_strlen($key, '8bit') : strlen($key);
  //logfile('debug_wui.log', 'Encrypt requested. Key: '.$key.', size: '.$key_len);
  if (!$key_len || $key_len > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    //echo $key;
    print_debug("Encryption key must be at least 1 and not more than ".SODIUM_CRYPTO_SECRETBOX_KEYBYTES.".");
    return FALSE;
  } elseif ($key_len < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    $key = sodium_pad($key, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    //logfile('debug_wui.log', 'Padded Key: '.$key.', size: '.strlen($key));
  }

  if (!$nonce) {
    // Sodium encrypt require $nonce
    // if not set use server unique id
    $nonce = get_unique_id();
  }

  // Fix nonce length
  $nonce_len = check_extension_exists('mbstring') ? mb_strlen($nonce, '8bit') : strlen($nonce);
  if ($nonce_len > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
    $nonce = substr($nonce, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  } elseif ($nonce_len < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
    $nonce = sodium_pad($nonce, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  }

  try {
    $encrypted = safe_base64_encode(sodium_crypto_secretbox($string, $nonce, $key));
  } catch (SodiumException $exception) {
    //var_dump($exception);
    print_debug($exception->getMessage());
    $encrypted = FALSE;
  }
  //var_dump($encrypted);
  return $encrypted;
}

function decrypt_sodium($encrypted, $key, $nonce = NULL) {
  // https://dev.to/paragonie/libsodium-quick-reference#crypto-secretbox

  $key_len = check_extension_exists('mbstring') ? mb_strlen($key, '8bit') : strlen($key);
  //logfile('debug_wui.log', 'Decrypt requested. Key: '.$key.', size: '.$key_len);
  if ($key_len > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    return FALSE;
  } elseif ($key_len < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    $key = sodium_pad($key, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    //logfile('debug_wui.log', 'Padded Key: '.$key.', size: '.strlen($key));
  }

  if (!$nonce) {
    // Sodium encrypt require $nonce
    // if not set use server unique id
    $nonce = get_unique_id();
  }
  // Fix nonce length
  $nonce_len = check_extension_exists('mbstring') ? mb_strlen($nonce, '8bit') : strlen($nonce);
  if ($nonce_len > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
    $nonce = substr($nonce, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  } elseif ($nonce_len < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
    $nonce = sodium_pad($nonce, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  }

  try {
    $string = sodium_crypto_secretbox_open(safe_base64_decode($encrypted), $nonce, $key);
  } catch (SodiumException $ex) {
    //var_dump($key);
    $string = FALSE;
  }
  return $string;
}

function encrypt_mcrypt($string, $key) {
  // Mcrypt deprecated in php > 7.0
  $key = pad_key($key);
  return safe_base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_ECB));
}

function decrypt_mcrypt($encrypted, $key) {
  // Mcrypt deprecated in php > 7.0
  $key = pad_key($key);
  return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, safe_base64_decode($encrypted), MCRYPT_MODE_ECB), "\t\n\r\0\x0B");
}

// DOCME needs phpdoc block
function safe_base64_encode($string) {
  $data = base64_encode($string);
  return str_replace(array('+','/','='), array('-','_',''), $data);
}

// DOCME needs phpdoc block
function safe_base64_decode($string) {
  $data = str_replace(array('-','_'), array('+','/'), $string);
  $mod4 = strlen($data) % 4;
  if ($mod4) {
    $data .= substr('====', $mod4);
  }
  return base64_decode($data);
}

// DOCME needs phpdoc block
function encrypt($string, $key, $nonce = NULL) {
  //logfile('debug_wui.log', 'Encrypt requested');

  if (!OBS_ENCRYPT) {
    print_debug("Encrypt unsupported. Please install mcrypt extension for php less than 7.2 or enable sodium extension for php 7.2 and greater!");
    return FALSE;
  }

  switch (OBS_ENCRYPT_MODULE) {
    case 'sodium':
      return encrypt_sodium($string, $key, $nonce);
      break;

    case 'mcrypt':
      return encrypt_mcrypt($string, $key);
      break;
  }
}

// DOCME needs phpdoc block
function decrypt($encrypted, $key, $nonce = NULL) {
  //logfile('debug_wui.log', 'Decrypt requested');

  if (!OBS_ENCRYPT) {
    print_debug("Encrypt unsupported. Please install mcrypt extension for php less than 7.2 or enable sodium extension for php 7.2 and greater!");
    return FALSE;
  }

  switch (OBS_ENCRYPT_MODULE) {
    case 'sodium':
      return decrypt_sodium($encrypted, $key, $nonce);
      break;

    case 'mcrypt':
      return decrypt_mcrypt($encrypted, $key);
      break;
  }
}

// This function required for encrypt/decrypt, since php 5.6
// see: http://stackoverflow.com/questions/27254432/mcrypt-decrypt-error-change-key-size
function pad_key($key) {
  // key is too large
  if (strlen($key) > 32) { return FALSE; }

  // set sizes
  $sizes = array(16, 24, 32);

  // loop through sizes and pad key
  foreach($sizes as $s) {
    while (strlen($key) < $s) {
      $key .= "\0";
    }
    if (strlen($key) === $s) { break; } // finish if the key matches a size
  }

  // return
  return $key;
}

// EOF