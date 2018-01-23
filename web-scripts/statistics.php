<?php

$columns = array(
  'stable',
  'tasks',
  'pending_packages',
  'staging',
  'testing',
  'broken',
  'loops',
  'looped_packages',
  'locked',
  'blocked',
  'next_pending',
  'tested'
);

$print_columns = array(
  'tasks',
  'pending_packages',
  'staging',
  'testing',
  'tested',
  'broken',
  'loops',
  'looped_packages',
  'locked',
  'blocked',
  'next_pending'
);

$t_min = -1;
$t_max = -1;
$val_max = -1;

foreach (explode("\n",trim(file_get_contents('/srv/http/statistics'))) as $val) {
  $val = explode(" ",$val);
  if (($t_min == -1) || ($t_min > $val[0]))
    $t_min = $val[0];
  if (($t_max == -1) || ($t_max < $val[0]))
    $t_max = $val[0];
  foreach ($columns as $id => $column) {
    if (count($val) > $id+1)
      $values[$column][$val[0]] = $val[$id+1];
  };
  foreach ($print_columns as $column) {
    if (array_key_exists($column,$values))
      if (($val_max == -1) || ($val_max < $values[$column][$val[0]]))
        $val_max = $values[$column][$val[0]];
  }
};

$max_len = 0;
foreach ($print_columns as $column) {
  $len = strlen($values[$column][$t_max])+1;
  if ($len > $max_len)
    $max_len = $len;
}

$width = 1600;
$height = 600;
$border = 5;
$legend_line_length = 10;
$legend_height = 3 * ImageFontHeight(5) + $legend_line_length;

$im = @ImageCreate ($width + $legend_line_length + $max_len * ImageFontWidth(5), $height + $legend_height)
      or die ("Cannot create new gd-image-stream");

$background_color = ImageColorAllocate ($im, 255, 255, 255);
$foreground_color = ImageColorAllocate ($im, 0, 0, 0);

$colors['stable'] = ImageColorAllocate ($im, 0, 0, 0);
$colors['tasks'] = ImageColorAllocate ($im, 0, 0, 128);
$colors['pending_packages'] = ImageColorAllocate ($im, 0, 0, 255);
$colors['staging'] = ImageColorAllocate ($im, 0, 100, 0);
$colors['testing'] = ImageColorAllocate ($im, 0, 200, 0);
$colors['tested'] = ImageColorAllocate ($im, 100, 255, 0);
$colors['broken'] = ImageColorAllocate ($im, 255, 0, 0);
$colors['loops'] = ImageColorAllocate ($im, 128, 128, 0);
$colors['looped_packages'] = ImageColorAllocate ($im, 255, 128, 128);
$colors['locked'] = ImageColorAllocate ($im, 128, 128, 128);
$colors['blocked'] = ImageColorAllocate ($im, 128, 0, 0);
$colors['next_pending'] = ImageColorAllocate ($im, 0, 255, 255);

function scale($x, $x_min, $x_max, $scale, $log) {
  if ($log) {
    $x = log($x + 10);
    $x_min = log($x_min + 10);
    $x_max = log($x_max + 10);
  };
  if ($x_max == $x_min)
    $frac = 0;
  else
    $frac = ($x - $x_min)/($x_max - $x_min);
  if ($scale < 0)
    return ($frac-1) * $scale;
  else
    return $frac * $scale;
};

function print_graph($data, $color) {
  global $width, $height, $im, $t_min, $t_max, $val_max, $border, $legend_line_length;
  ksort($data);
  $last_t = -1;
  $last_val = -1;
  foreach ($data as $t => $val) {
    if ($last_t != -1)
      ImageLine(
        $im,
        scale($last_t,$t_min,$t_max,$width-2*$border,false)+$border+$legend_line_length,
        scale($last_val,0,$val_max,-$height+2*$border,isset($_GET["log"]))+$border,
        scale($t,$t_min,$t_max,$width-2*$border,false)+$border+$legend_line_length,
        scale($val,0,$val_max,-$height+2*$border,isset($_GET["log"]))+$border,
        $color
      );
    $last_t = $t;
    $last_val = $val;
  }
  ImageString(
    $im,
    5,
    $width+$legend_line_length,
    scale($last_val,0,$val_max,-$height+2*$border,isset($_GET["log"]))+$border - ImageFontHeight(5)/2,
    " ".$data[$t_max],
    $color
  );
};

ImageRectangle($im, $legend_line_length, 0, $width-1+$legend_line_length, $height-1, $foreground_color);

ImageString($im, 5, $legend_line_length, $height + $legend_line_length + 2*ImageFontHeight(5), "( ".trim(shell_exec("uptime | sed 's|^.*\\s\\(load\\)|\\1|'"))." )", $foreground_color);

$xpos = $legend_line_length;
foreach ($print_columns as $column) {
  print_graph($values[$column], $colors[$column]);
  ImageString($im, 5, $xpos, $height + $legend_line_length + ImageFontHeight(5), $column, $colors[$column]);
  $xpos += (strlen($column) + 1.75) * ImageFontWidth(5);
}

ImageString($im, 5, $legend_line_length, $height + $legend_line_length, date('Y-m-d H:i', $t_min), $foreground_color);
$s = date('Y-m-d H:i', $t_max);
ImageString($im, 5, $width+$legend_line_length - strlen($s)*ImageFontWidth(5), $height + $legend_line_length, $s, $foreground_color);

for ($t=ceil($t_min/24/60/60); $t<=floor($t_max/24/60/60); $t++)
  ImageLine(
    $im,
    scale($t*24*60*60,$t_min,$t_max,$width-2*$border,false)+$border+$legend_line_length,
    $height,
    scale($t*24*60*60,$t_min,$t_max,$width-2*$border,false)+$border+$legend_line_length,
    $height+$legend_line_length,
    $foreground_color
  );

for ($val=0; $val<=$val_max;) {
  ImageLine(
    $im,
    0,
    scale($val,0,$val_max,-$height+2*$border,isset($_GET["log"]))+$border,
    $legend_line_length,
    scale($val,0,$val_max,-$height+2*$border,isset($_GET["log"]))+$border,
    $foreground_color
  );
  if (! isset($_GET["log"]))
    $val+=pow(10,round(log($val_max)/log(10))-1);
  elseif ($val==0)
    $val++;
  else
    $val=$val*10;
}

// ImageString ($im, 1, 5, 5, "Test-String ".rand(), $foreground_color);

header ("Content-type: image/png");

ImagePNG ($im);

// passthru('wc -l /srv/http/statistics');

?>
