<?php

// Stands in for the server's mail program (sendmail) in tests: keeps what it was handed so the test can read it.
$dir = __DIR__.'/../../storage/framework/testing';
is_dir($dir) || mkdir($dir, 0777, true);
file_put_contents($dir.'/fake_sendmail_out.txt', stream_get_contents(STDIN)."\n".implode(' ', array_slice($argv, 1)));
exit(0);
