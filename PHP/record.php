<?php
file_put_contents(__DIR__.'/log/gps.'.date('YmdHis').'.log', file_get_contents('php://input'));
