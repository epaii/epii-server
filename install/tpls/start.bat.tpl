set driver=%~dp0
cd %driver%
set driver=%driver:~0,2%
%driver%

{{php_cmd}} ./default/start.php

pause