<?php
/**
 * This file is a duplicate of plugins/download_by_size/action.php, I have only change
 * PHPWG_ROOT_PATH and added a few lines between "//---- specific ppcredits, start"
 * and "//---- specific ppcredits, end"
 */

define('PHPWG_ROOT_PATH','../../'); // path 
session_cache_limiter('public');
include_once(PHPWG_ROOT_PATH.'include/common.inc.php');

// Check Access and exit when user status is not ok
check_status(ACCESS_GUEST);

function guess_mime_type($ext)
{
  switch ( strtolower($ext) )
  {
    case "jpe": case "jpeg":
    case "jpg": $ctype="image/jpeg"; break;
    case "png": $ctype="image/png"; break;
    case "gif": $ctype="image/gif"; break;
    case "tiff":
    case "tif": $ctype="image/tiff"; break;
    case "txt": $ctype="text/plain"; break;
    case "html":
    case "htm": $ctype="text/html"; break;
    case "xml": $ctype="text/xml"; break;
    case "pdf": $ctype="application/pdf"; break;
    case "zip": $ctype="application/zip"; break;
    case "ogg": $ctype="application/ogg"; break;
    default: $ctype="application/octet-stream";
  }
  return $ctype;
}

function do_error( $code, $str )
{
  set_status_header( $code );
  echo $str ;
  exit();
}

/**
 * compared to i.php, this function does not apply the watermark (and only
 * keep code relevant for ppcredits plugin)
 */
function ppcredits_generate_temporary_derivative($element_info, $type, $params)
{
  global $conf;
  
  $src_path = PHPWG_ROOT_PATH.$element_info['path'];
  $derivative_filename = $element_info['id'].'-'.$type.'-'.generate_key(20).'.'.get_extension($element_info['path']);
  $derivative_path = PHPWG_ROOT_PATH.$conf['data_location'].'prepaid_credits/'.$derivative_filename;

  include_once(PHPWG_ROOT_PATH.'admin/include/image.class.php');

  if (!isset($element_info['rotation']))
  {
    $rotation_angle = pwg_image::get_rotation_angle($src_path);
  }
  else
  {
    $rotation_angle = pwg_image::get_rotation_angle_from_code($element_info['rotation']);
  }

  if (!mkgetdir(dirname($derivative_path)))
  {
    die("dir create error");
  }

  ignore_user_abort(true);
  @set_time_limit(0);

  $image = new pwg_image($src_path);

  // rotate
  if (0 != $rotation_angle)
  {
    $image->rotate($rotation_angle);
  }

  // Crop & scale
  $o_size = $d_size = array($image->get_width(),$image->get_height());
  $params->sizing->compute($o_size, $element_info['coi'], $crop_rect, $scaled_size);
  if ($crop_rect)
  {
    $image->crop( $crop_rect->width(), $crop_rect->height(), $crop_rect->l, $crop_rect->t);
  }

  if ($scaled_size)
  {
    $image->resize( $scaled_size[0], $scaled_size[1] );
    $d_size = $scaled_size;
  }

  if ($params->sharpen)
  {
    $image->sharpen( $params->sharpen );
  }

  if ($d_size[0]*$d_size[1] < $conf['derivatives_strip_metadata_threshold'])
  {// strip metadata for small images
    $image->strip();
  }

  $image->set_compression_quality(ImageStdParams::$quality);
  $image->write($derivative_path);
  $image->destroy();

  return $derivative_path;
}

if (!isset($_GET['id'])
    or !is_numeric($_GET['id'])
    or !isset($_GET['part'])
    or !in_array($_GET['part'], array('e','r') ) )
{
  do_error(400, 'Invalid request - id/part');
}

$query = '
SELECT * FROM '. IMAGES_TABLE.'
  WHERE id='.$_GET['id'].'
;';

$element_info = pwg_db_fetch_assoc(pwg_query($query));
if ( empty($element_info) )
{
  do_error(404, 'Requested id not found');
}

$sizes_purchased = array_fill_keys(ppcredits_recently_purchased_sizes($_GET['id']), 1);

// $filter['visible_categories'] and $filter['visible_images']
// are not used because it's not necessary (filter <> restriction)
$query='
SELECT id
  FROM '.CATEGORIES_TABLE.'
    INNER JOIN '.IMAGE_CATEGORY_TABLE.' ON category_id = id
  WHERE image_id = '.$_GET['id'].'
'.get_sql_condition_FandF(
  array(
      'forbidden_categories' => 'category_id',
      'forbidden_images' => 'image_id',
    ),
  '    AND'
  ).'
  LIMIT 1
;';
if ( pwg_db_num_rows(pwg_query($query))<1 )
{
  do_error(401, 'Access denied');
}

include_once(PHPWG_ROOT_PATH.'include/functions_picture.inc.php');
$file='';
switch ($_GET['part'])
{
  case 'e':
    //---- specific ppcredits, start
    if (isset($_GET['size']) and 'original' != $_GET['size'])
    {
      foreach (ImageStdParams::get_all_type_map() as $type => $params)
      {
        if ($type == $_GET['size'])
        {
          $page['derivative_type'] = $type;
          $page['derivative_params'] = $params;
          break;
        }
      }

      if (!isset($page['derivative_type']))
      {
        die('Hacking attempt: unknown size');
      }

      if (!isset($sizes_purchased[ $_GET['size'] ]))
      {
        die('Hacking attempt: size '.$_GET['size'].' not purchased');
      }

      $file = ppcredits_generate_temporary_derivative(
        $element_info,
        $page['derivative_type'],
        $page['derivative_params']
        );
      
      $page['delete_temporary_derivative'] = true;
      $size = getimagesize($file);

      // change the name of the file for download, suffix with _widthxheight before the extension
      $element_info['file'] = ppcredits_getFilename(
        $element_info,
        array(
          'width'=>$size[0],
          'height'=>$size[1]
          )
        );
    }
    else
    {
      if (!isset($sizes_purchased['original']))
      {
        die('Hacking attempt: size '.$_GET['size'].' not purchased');
      }

    // if ( !$user['enabled_high'] )
    // {
    //   $deriv = new DerivativeImage(IMG_XXLARGE, new SrcImage($element_info));
    //   if ( !$deriv->same_as_source() )
    //   {
    //     do_error(401, 'Access denied e');
    //   }
    // }
    //---- specific ppcredits, end
    $file = get_element_path($element_info);
    //---- specific ppcredits, start
    }
    //---- specific ppcredits, end
    
    break;
  case 'r':
    $file = original_to_representative( get_element_path($element_info), $element_info['representative_ext'] );
    break;
}

if ( empty($file) )
{
  do_error(404, 'Requested file not found');
}

if ($_GET['part'] == 'e') {
  pwg_log($_GET['id'], 'high');
}
else if ($_GET['part'] == 'e')
{
  pwg_log($_GET['id'], 'other');
}

$http_headers = array();

$ctype = null;
if (!url_is_remote($file))
{
  if ( !@is_readable($file) )
  {
    do_error(404, "Requested file not found - $file");
  }
  $http_headers[] = 'Content-Length: '.@filesize($file);
  if ( function_exists('mime_content_type') )
  {
    $ctype = mime_content_type($file);
  }

  $gmt_mtime = gmdate('D, d M Y H:i:s', filemtime($file)).' GMT';
  $http_headers[] = 'Last-Modified: '.$gmt_mtime;

  // following lines would indicate how the client should handle the cache
  /* $max_age=300;
  $http_headers[] = 'Expires: '.gmdate('D, d M Y H:i:s', time()+$max_age).' GMT';
  // HTTP/1.1 only
  $http_headers[] = 'Cache-Control: private, must-revalidate, max-age='.$max_age;*/

  if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
  {
    set_status_header(304);
    foreach ($http_headers as $header)
    {
      header( $header );
    }
    exit();
  }
}

if (!isset($ctype))
{ // give it a guess
  $ctype = guess_mime_type( get_extension($file) );
}

$http_headers[] = 'Content-Type: '.$ctype;

if (isset($_GET['download']))
{
  $http_headers[] = 'Content-Disposition: attachment; filename="'.$element_info['file'].'";';
  $http_headers[] = 'Content-Transfer-Encoding: binary';
}
else
{
  $http_headers[] = 'Content-Disposition: inline; filename="'
            .basename($file).'";';
}

foreach ($http_headers as $header)
{
  header( $header );
}

// Looking at the safe_mode configuration for execution time
if (ini_get('safe_mode') == 0)
{
  @set_time_limit(0);
}

@readfile($file);

if (isset($page['delete_temporary_derivative']) and $page['delete_temporary_derivative'])
{
  unlink($file);
}
?>