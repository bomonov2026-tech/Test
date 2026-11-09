<?php
/* Copy to ops-config.php beside index.php. Never send this secret to the browser. */
return array(
    'ws_url' => 'wss://YOUR-GAME-SERVER.example/ws',
    'shared_secret' => 'REPLACE_WITH_THE_SAME_RANDOM_SECRET_AS_THE_NODE_SERVER'
);
