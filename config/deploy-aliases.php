<?php

$aliasesString = env('DEPLOY_ALIASES_TO_SSH_CREDS', 'acrossoffwest\/(.*):aow,acrossoffwest/task-manager:aow');

$aliases = array_map('trim', explode(',', $aliasesString));

$result = [];
foreach ($aliases as $alias) {
    $aliasExploded = explode(':', $alias);
    if (count($aliasExploded) < 2) {
        continue;
    }
    $result[$aliasExploded[0]] = $aliasExploded[1];
}


return $result;
