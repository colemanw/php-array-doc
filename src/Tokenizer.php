<?php

namespace PhpArrayDocument;

if (!defined('T_FN')) {
  define('T_FN', '_polyfill_T_FN');
}

class Tokenizer {

  public static function getTokens(string $content): array {
    return array_map([__CLASS__, 'normalizeToken'], token_get_all($content));
  }

  /**
   * @param int|string|array $token
   * @return string
   */
  public static function getName($token): ?string {
    $id = is_array($token) ? $token[0] : $token;
    if (is_string($id) && strlen($id) === 1) {
      return $id;
    }
    if (is_string($id) && strpos($id, '_polyfill_') === 0) {
      return substr($id, strlen('_polyfill_'));
    }
    if ($id === NULL) {
      return NULL;
    }
    return token_name($id);
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
