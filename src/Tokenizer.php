<?php

namespace PhpArrayDocument;

if (!defined('T_FN')) {
  define('T_FN', '_polyfill_tn');
}

class Tokenizer {

  public static function getTokens(string $content): array {
    return array_map([__CLASS__, 'normalizeToken'], token_get_all($content));
  }

  /**
   * There may be small differences in how token_get_all() behaves on different versions of PHP.
   *
   * @param array|string $token
   *   Ex: '('
   *   Ex: [T_STRING, 'hello_world', ...]
   * @return array|string
   */
  protected static function normalizeToken($token) {
    if (is_array($token) && $token[0] === T_STRING && $token[1] === 'fn') {
      $token[0] = T_FN;
    }
    return $token;
  }

}
