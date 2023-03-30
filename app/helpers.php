<?php

if (!function_exists('jPublicKey')) {
  function jPublicKey()
  {
    return '-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCLy7wi6vVA/k5StJziTUasUPlJ+nVZuAD8Om9sRNuOaJryBUAVY7LwoFIU+aMqVVw1Jl5PENxqJeQf+RtCR7BWn2j1cjX0ch3xHFq9a1ixoqKDJBNq/KmRs5SjLqWSHwU59zv0KNdtKr8pv+JN+cln/2JzazM/KVQ0GsoOhGxxTQIDAQAB\n-----END PUBLIC KEY-----';
  }
}

if (!function_exists('jUuid')) {
  function jUuid()
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for the time_low
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      // 16 bits for the time_mid
      mt_rand(0, 0xffff),
      // 16 bits for the time_hi,
      mt_rand(0, 0x0fff) | 0x4000,

      // 8 bits and 16 bits for the clk_seq_hi_res,
      // 8 bits for the clk_seq_low,
      mt_rand(0, 0x3fff) | 0x8000,
      // 48 bits for the node
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}
