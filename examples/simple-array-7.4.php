<?php
return [
  'name' => 'hello',
  'label' => 'Hello World!',
  'active' => TRUE,
  'html' => [
    'bold' => TRUE,
  ],
  'details' => fn() => [
    'zero' => 0,
    'one' => 1,
    'null' => NULL,
    'true' => TRUE,
    'false' => FALSE,
    'float' => 12.3,
    'stringNum' => '45.6',
    'alskjdf asdf' => 123,
  ],
];
