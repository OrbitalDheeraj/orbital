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
$curr_user_id=get_current_user_id();

echo '
<h3><span style="color: #663399">Event Bookings</span></h3>
<table style="width:100%" style="border: 3px solid black; border-radius: 0px">
  <tr>
    <th ><font color="white">S/N</font></th>
    <th ><font color="white">Event Name</font></th>
    <th ><font color="white">Amount Owed</font></th>
    <th ><font color="white">Amount Paid</font></th>
    <th ><font color="white">Event Date</font></th>
    <th ><font color="white"></font></th>
  </tr>';

$query=mysql_query("SELECT event, details, amountpaid, rsvpDate, id FROM wp_rsvpmaker WHERE user_id='$curr_user_id' ORDER BY rsvpDate DESC");
if($query){
$count=1;
while($event_id_list=mysql_fetch_array($query)){
  $event_title=get_the_title($event_id_list['event']);
  $details=unserialize($event_id_list['details']);
  $owed=($details['total'] - $event_id_list["amountpaid"]);
  $paid=$event_id_list["amountpaid"];
  $eventId=$event_id_list["id"];
  $current_date=date("Y-m-d H:i:s");
  $rsvp_date=$event_id_list["rsvpDate"];
   

  $upcoming=false;
  if($current_date<$rsvp_date)
  $upcoming=true; 

echo '<tr>
<td>'.$count.'</td>
<td>'.$event_title.'</td>
<td>$'.$owed.'</td>
<td>$'.$paid.'</td>
<td>'.date("D jS M Y",strtotime($rsvp_date)).'</td>';
if($upcoming==true){
echo '<td><div class="toolbar"><a href="http://nuscaa.co.nf/events/my-bookings/?recordId='.$eventId.'">Cancel</a></div></td></tr>';
}
elseif ($upcoming==false){
 echo '<td><font color="grey">Elapsed</font></td></tr>';
}
$count++; 
}
echo '</table>';
}

if(isset($_GET['recordId'])){
   $id=mysql_real_escape_string($_GET['recordId']);
   $delete_row=mysql_query("DELETE FROM wp_rsvpmaker WHERE id='$id'");
   header("Location: http://nuscaa.co.nf/events/my-bookings/");
   exit();
}
}

if(!is_user_logged_in()){
  echo '<h4>Please <a href="http://nuscaa.co.nf/login/">log in</a> to continue</h4>';
}
?>
