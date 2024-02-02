<?return array (
  0 => 
  array (
    'patterns' => '/logout',
    'paths' => 
    array (
      'controller' => 'index',
      'action' => 'logout',
      'params' => 1,
    ),
  ),
  1 => 
  array (
    'patterns' => '/services/{id:[0-9]{1,}}',
    'paths' => 
    array (
      'controller' => 'services',
      'action' => 'service',
    ),
  ),
  2 => 
  array (
    'patterns' => '/contracts/{id}/{act:[a-zA-Z0-9_-]{1,}}',
    'paths' => 
    array (
      'controller' => 'contracts',
      'action' => 'contract',
    ),
  ),
  3 => 
  array (
    'patterns' => '/contracts/{id:[0-9]{1,}}',
    'paths' => 
    array (
      'controller' => 'contracts',
      'action' => 'contract',
    ),
  ),
  4 => 
  array (
    'patterns' => '/counteragents/{id}/{act:[a-zA-Z0-9_-]{1,}}',
    'paths' => 
    array (
      'controller' => 'counteragents',
      'action' => 'agent',
    ),
  ),
  5 => 
  array (
    'patterns' => '/counteragents/{id:[0-9]{1,}}',
    'paths' => 
    array (
      'controller' => 'counteragents',
      'action' => 'agent',
    ),
  ),
  6 => 
  array (
    'patterns' => '/counteragents/api/{method}',
    'paths' => 
    array (
      'controller' => 'counteragents',
      'action' => 'api',
      'params' => 1,
    ),
  ),
  7 => 
  array (
    'patterns' => '/contracts/api/{method}',
    'paths' => 
    array (
      'controller' => 'contracts',
      'action' => 'api',
      'params' => 1,
    ),
  ),
);