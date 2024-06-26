<?php
use Civi\Core\SettingsDefinition;
use CRM_Mosaico_ExtensionUtil as E;

// About this doc
return SettingsDefinition::create([
  /* The name is important */
  'name' => 'hello',
  // The label is shown to somebody
  'label' => E::ts('Hello World!'),
  'active' => TRUE,
  'html' => [
    // To boldly go
    'bold' => TRUE,
  ],
  'details' => fn() => [
    'alskjdf asdf' => 123,
  ],
]);
