<?php 
 function test($a,$b)
{
 $t = strlen($b);
 $k = substr($a, -$t);
if(strcmp($k,$b))
{
  print "false123";
}
else
{
print "true";
}
}

echo test("ankit","it");
?>
