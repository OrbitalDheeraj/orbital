<?php
if(is_user_logged_in()){
  $con=mysql_connect ('pdb3.biz.nf','2165696_27ae','s78jHxcX') or die("Could not connect: ".mysql_error());
 mysql_select_db('2165696_27ae');
/*
if($con)
    echo 'Successfully connected to database';
  else
    echo 'Database connection error.';
*/

$current_user=wp_get_current_user();
$curr_username=$current_user->user_login;
$user_id=get_current_user_id();


 $first_time_users_check="SELECT phone_num FROM wp_users WHERE user_login='$curr_username' AND first_name='' AND last_name=''";
 $sql=mysql_query($first_time_users_check);
 $rows=mysql_num_rows($sql);

if($rows==0){
echo '<p><strong><font color="Orange">To add or update your data, edit the necessary fields and click "Update".</font></strong></p></ br>';
}
 if($_SERVER['REQUEST_METHOD']=='POST'){ 
$phone_num=trim($_POST['phone_num']);
$phone_num_length=strlen($phone_num);
$phone_digits=is_numeric($phone_num);


if($rows==0){
   if(!empty($_POST['first_name'])){
     $maj_update=mysql_query("UPDATE wp_users SET major='".$_POST['first_name']."' WHERE user_login='".$curr_username."'");
    }

if(!empty($_POST['last_name'])){
     $maj_update=mysql_query("UPDATE wp_users SET major='".$_POST['last_name']."' WHERE user_login='".$curr_username."'");
    }

if(!empty($_POST['major'])){
     $maj_update=mysql_query("UPDATE wp_users SET major='".$_POST['major']."' WHERE user_login='".$curr_username."'");
    }

   if(!empty($_POST['year_of_graduation']) && $_POST['year_of_graduation']!=""){
     $maj_update=mysql_query("UPDATE wp_users SET year_of_graduation='".$_POST['year_of_graduation']."' WHERE user_login='".$curr_username."'");
    }

   if(!empty($_POST['curr_company_name']) && $_POST['curr_company_name']!=""){
     $maj_update=mysql_query("UPDATE wp_users SET curr_company_name='".$_POST['curr_company_name']."' WHERE user_login='".$curr_username."'");
    }

   if(!empty($_POST['job_title'])){
     $maj_update=mysql_query("UPDATE wp_users SET job_title='".$_POST['job_title']."' WHERE user_login='".$curr_username."'");
    }

   if(!empty($_POST['phone_num'])){
      if($phone_num_length==8 && $phone_digits){
     $maj_update=mysql_query("UPDATE wp_users SET phone_num='".$_POST['phone_num']."' WHERE user_login='".$curr_username."'");
} 
  else if($phone_num_length!=8||!$phone_digits){
    echo 'Enter a valid phone number<br />';
  }
}
  if(!empty($_POST['pass1'])){
   if($_POST['pass1']!=$_POST['pass2']){
     echo 'Your passwords do not match with each other.';
  }else{
    $password_first=$_POST['pass1'];
    wp_set_password($password_first, $user_id);
  }
}
/* Testing re-direct only
$pagelist = get_pages('sort_column=menu_order&sort_order=asc');
$pages = array();
foreach ($pagelist as $page) {
   $pages[] += $page->ID;
}

$current = array_search(get_the_ID(), $pages);
$prevID = $pages[$current-1];
$nextID = $pages[$current+1];
$prev=get_permalink($prevID);

header("location:$prev");
   exit();
*/
}



if($rows==1){
 $errors=array();
if(empty($_POST['first_name'])){
   $errors[]='Please enter your first name';
} else{
  $fn=trim($_POST['first_name']);
}

if(empty($_POST['last_name'])){
   $errors[]='Please enter your last name';
} else{
  $ln=trim($_POST['last_name']);
}


if(empty($_POST['phone_num'])){
   $errors[]='Please enter your phone number';
} else{
   $phone_num=trim($_POST['phone_num']);
}

$phone_num_length=strlen($phone_num);
if($phone_num_length!=8||!$phone_digits){
  $errors[]='Enter valid phone number';
}

if(!empty($_POST['pass1'])){
   if($_POST['pass1']!=$_POST['pass2']){
     $errors[]='Your passwords do not match with each other';
  }else{
    $password_second=$_POST['pass1'];
    wp_set_password($password_second, $user_id);
  }
}


 $maj=trim($_POST['major']);
 $yog=trim($_POST['year_of_graduation']);
 $currcomp=trim($_POST['curr_company_name']);
 $jobtitle=trim($_POST['job_title']);

if(empty($errors)){
$sql="UPDATE wp_users SET first_name='".$fn."', last_name='".$ln."', major='".$maj."', year_of_graduation='".$yog."', curr_company_name='".$currcomp."', job_title='".$jobtitle."', phone_num='".$phone_num."' WHERE user_login='".$curr_username."'";
$query_result=mysql_query($sql) or die("Not working".error());

header("location:http://nuscaa.co.nf/upcoming-events/");
   exit();

}

  if(!empty($errors)){
   echo '<h1>Error!</h1>
   <p class="error">The following error(s) occurred:<br />';
   foreach($errors as $msg){
   echo "-$msg<br />\n";
   }
  echo '</p><p>Please try again.</p><p><br /></p>';
}


 if(!empty($_POST['major'])&&!empty($_POST['first_name'])&&!empty($_POST['last_name'])&&!empty($_POST['curr_company_name'])&&!empty($_POST['job_title'])&&!empty($_POST['year_of_graduation'])){
$major=$_REQUEST['major'];
$first_name=$_REQUEST['first_name'];
$last_name=$_REQUEST['last_name'];
$curr_company=$_REQUEST['curr_company_name'];
$job_title=$_REQUEST['job_title'];
$yog=$_REQUEST['year_of_graduation'];

echo "<p>Thank you, <b>$first_name</b> <b>$last_name</b>, the major you have chosen is <b>$major</b> and you are from class of $yog.</p>\n";
echo "<p>You are currently working in <b>$curr_company</b> as a <b>$job_title</b>.</p>\n";
}
}
}
}
?>

<form action="" method="post">
<h3>Personal Particulars</h3>

<?php $current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
echo '<b><p>Username: </b><h4>'.$curr_username.'</h4></p>';?>

<?php $current_user=wp_get_current_user();
 $email=$current_user->user_email; 
 echo '<b><p>E-mail: </b><h4>'.$email.'</h4></p>'; ?>

<b><p>First Name <font color="red">*</font> :</b><input type="text" name="first_name" size="11" maxlength="20" value="<?php 
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
$sql_first_name=mysql_query("SELECT first_name FROM wp_users WHERE user_login='$curr_username'");
$result_first_name=mysql_fetch_array($sql_first_name);
echo $result_first_name['first_name'];?>"/></p>

<p><b>Last Name <font color="red">*</font> :</b></b><input type="text" name="last_name" size="11" maxlength="40" value="<?php 
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
$sql_last_name=mysql_query("SELECT last_name FROM wp_users WHERE user_login='$curr_username'");
$result_last_name=mysql_fetch_array($sql_last_name);
echo $result_last_name['last_name'];?>"/></p>

<b><p>Contact Number <font color="red">*</font> :</b></b><input type="text" name="phone_num" value="<?php 
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
$sql_phone_num=mysql_query("SELECT phone_num FROM wp_users WHERE user_login='$curr_username'");
$result_phone_num=mysql_fetch_array($sql_phone_num);
echo $result_phone_num['phone_num'];?>" /></p><br />

<?php
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;

$sql_major=mysql_query("SELECT major FROM wp_users WHERE user_login='$curr_username'");
if($sql_major){
$result_major=mysql_fetch_array($sql_major);
$rows=mysql_num_rows($sql_major);
}
?>

<h3>Qualification Details</h3>
<p><b><label>Major: </b><select name="major">
<?php if($rows==0){?>
<option value="" selected>--- Select Major ---</option>
<?php } ?>
<option value="">--- Select Major ---</option>

<?php if($result_major['major']=="Computer Science"){ ?>
<option value="Computer Science" selected>Computer Science</option>
<?php } ?>
<option value="Computer Science">Computer Science</option>

<?php if($result_major['major']=="Computer Engineering"){ ?>
<option value="Computer Engineering" selected>Computer Engineering</option>
<?php } ?>

<option value="Computer Engineering">Computer Engineering</option>

<?php if($result_major['major']=="Information Systems"){ ?>
<option value="Information Systems" selected>Information Systems</option>
<?php } ?>
<option value="Information Systems">Information Systems</option>

<?php if($result_major['major']=="Business Analytics"){ ?>
<option value="Business Analytics" selected>Business Analytics</option>
<?php } ?>
<option value="Business Analytics">Business Analytics</option>

<?php if($result_major['major']=="Computational Biology"){ ?>
<option value="Computational Biology" selected>Computational Biology</option>
<?php } ?>
<option value="Computational Biology">Computational Biology</option>

<?php if($result_major['major']=="Others"){ ?>
<option value="Others" selected>Others</option>
<?php } ?>
<option value="Others">Others</option></select></label></p>


<b><p><label>Year of Graduation:</b> <select name='year_of_graduation'>
<?php
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;

$sql_yog=mysql_query("SELECT year_of_graduation FROM wp_users WHERE user_login='$curr_username'");
if($sql_yog){
$result_yog=mysql_fetch_array($sql_yog);
$rows=mysql_num_rows($sql_yog);
}

$yog= range (1970, date("Y"));
if($rows==0)
echo "<option value=\"\" selected>---Select Year---</option>\n";
if($rows>0)
echo "<option value=\"\">---Select Year---</option>\n";

foreach($yog as $value){
if($result_yog['year_of_graduation']==$value)
echo "<option value=\"$value\" selected>$value</option>\n";
echo "<option value=\"$value\">$value</option>\n";
}
?></select></label></p>

<b><p>Current Company Name:</b><input type="text" name="curr_company_name" size="11" maxlength="40" value="<?php 
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
$sql_curr_company=mysql_query("SELECT curr_company_name FROM wp_users WHERE user_login='$curr_username'");
$result_company_name=mysql_fetch_array($sql_curr_company);
echo $result_company_name['curr_company_name'];?>"/></p>

<b><p>Job Title:</b><input type="text" name="job_title" size="11" maxlength="40" value="<?
$current_user=wp_get_current_user(); 
$curr_username=$current_user->user_login;
$sql_job_title=mysql_query("SELECT job_title FROM wp_users WHERE user_login='$curr_username'");
$result_job_title=mysql_fetch_array($sql_job_title);
 echo $result_job_title['job_title'];?>"/></p><br />

<h3>Change Password (Optional)</h3>
<p><b>New Password</b><input type="password" name="pass1" size="10" maxlength="20" value="<?php if(isset($_POST['pass1'])) echo $_POST['pass1'];?>" /></p>
<p><b>Confirm password</b><input type="password" name="pass2" size="10" maxlength="20" value="<?php if(isset($_POST['pass2'])) echo $_POST['pass2'];?>" /></p><br />
<?php 
  if(is_user_logged_in()){
?>
<p><input type="submit" name="update" value="Update"/></p>
<?php
  }
elseif(!is_user_logged_in()){
 header("Location: http://nuscaa.co.nf/login/");
   exit();
}
?>
</form>