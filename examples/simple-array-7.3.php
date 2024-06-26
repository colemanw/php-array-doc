<?php
return [
  'name' => 'hello',
  'label' => 'Hello World!',
  'active' => TRUE,
  'html' => [
    'bold' => TRUE,
  ],
  'details' => function() { return [
    'zero' => 0,
    'one' => 1,
    'true' => TRUE,
    'false' => FALSE,
    'alskjdf asdf' => 123,
  ]; },
];
