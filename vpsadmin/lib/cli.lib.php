<?php
/**
  * Writes line to CMD
  *
  * @param String $string - text
  **/
function writeln($string="")
{
  echo $string."\n";
}

/**
  * Reads password from STDIN
  * WARNING: this works only on UNIX like systems
  * 
  * @return String password 
  **/
function read_pwd()
{
  exec("stty -echo"); // disables cmd output
  usleep(300000); // circa 0.3 second is needed until stty -echo takes effect
  $password = read(); 
  exec("stty echo"); // enables cmd output

  return $password;
}

/** 
  * Reads string from STDIN
  *
  * @return String the string
  **/
function read()
{
  return trim (fgets (STDIN));
}

/**
  * Creates Yes/No question dialog. It waits until user types either Y or N (non-case sensitive)
  * 
  * @param String $q the question
  *
  * @return String answer
  **/ 
function read_yn($q)
{
  $ans = '';

  while (1) {
    writeln ($q.(" [Y/N]"));
    $ans = read();
    if (strtoupper($ans) == ('Y') || strtoupper($ans) == ('N'))
      break;
  }

  return $ans;
}

/**
  * Creates count down with 1 sec steps
  * 
  * @param String $text the text of the countdown
  * @param int $secs number of seconds for the countdown
  **/
function count_down($text, $secs)
{
  writeln($text);
  writeln(("Press CTRL+C to interrupt the countdown."));

  for ( ; $secs>0; $secs--) 
  {
    echo $secs.".. ";
    sleep (1);
  }

  writeln(); // new line after the countdown
}

/** 
  * Generates file in given file path with given content
  * WARNING: existing file will be overwritten!!
  *
  * @param String $file_path - path of the file
  * @param String $content - content of the file
  *
  * @return boolean - true on success, false on failure
  **/
function generate_file ($file_path, $content)
{
  if (($f = fopen($file_path, "w")) == null) 
    return false;

  fwrite ($f, $content);

  fclose($f);

  return true;
}

$_SESSION["is_admin"] = true;
$_SESSION["cli_mode"] = true;
$_SESSION["member"]["m_id"] = 1;
?>
