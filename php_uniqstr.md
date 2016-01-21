# Introduction #

php里面产生唯一字符串的函数，非常有用，位数为6位，字符串为0-9a-z之间，方便记忆


# Details #

Add your content here.  Format your content with:
  * Text in **bold** or _italic_
  * Headings, paragraphs, and lists
  * Automatic links to other wiki pages

```
<?php
function rand_uniqid()
{
    $in = time();
    $to_num = false;
    $passKey = rand(0,time());
    $pad_up = 6;
    $index = "abcdefghijklmnopqrstuvwxyz0123456789";
    if ($passKey !== null) {
        // Although this function's purpose is to just make the
        // ID short - and not so much secure,
        // you can optionally supply a password to make it harder
        // to calculate the corresponding numeric ID

        for ($n = 0; $n<strlen($index); $n++) {
            $i[] = substr( $index,$n ,1);
        }

        $passhash = hash('sha256',$passKey);
        $passhash = (strlen($passhash) < strlen($index))
            ? hash('sha512',$passKey)
            : $passhash;

        for ($n=0; $n < strlen($index); $n++) {
            $p[] =  substr($passhash, $n ,1);
        }

        array_multisort($p,  SORT_DESC, $i);
        $index = implode($i);
    }

    $base  = strlen($index);

    if ($to_num) {
        // Digital number  <<--  alphabet letter code
        $in  = strrev($in);
        $out = 0;
        $len = strlen($in) - 1;
        for ($t = 0; $t <= $len; $t++) {
            $bcpow = bcpow($base, $len - $t);
            $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
        }

        if (is_numeric($pad_up)) {
            $pad_up--;
            if ($pad_up > 0) {
                $out -= pow($base, $pad_up);
            }
        }
        $out = sprintf('%F', $out);
        $out = substr($out, 0, strpos($out, '.'));
    } else {
        // Digital number  -->>  alphabet letter code
        if (is_numeric($pad_up)) {
            $pad_up--;
            if ($pad_up > 0) {
                $in += pow($base, $pad_up);
            }
        }

        $out = "";
        for ($t = floor(log($in, $base)); $t >= 0; $t--) {
            $bcp = bcpow($base, $t);
            $a   = floor($in / $bcp) % $base;
            $out = $out . substr($index, $a, 1);
            $in  = $in - ($a * $bcp);
        }
        $out = strrev($out); // reverse
    }

    return $out;
}

echo rand_uniqid() . "\r\n";
?>
```

输出结果：
xqx1xp
8mi6i1
uax8x7