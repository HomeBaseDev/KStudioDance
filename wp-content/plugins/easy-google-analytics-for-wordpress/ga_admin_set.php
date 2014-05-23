 <?php
 if($_POST['_ga_acc_id'])
 update_option('_ga_acc_id',$_POST['_ga_acc_id']);
 ?>
 <style>
.wrap *{
    font-family: Tahoma;
    letter-spacing: 1px;
}

input[type=text],textarea{
    width:100px;
    padding:5px;
}

input{
   padding: 7px; 
}
</style>

<div class="wrap">
    <div class="icon32" id="icon-edit"><br></div>
<h2>Google Analytics Settings</h2>

<form action="" method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5">
<tr>
<td><nobr>Google Analytics Account Id:</nobr></td>
<td><input size="90" type="text" value="<?php echo get_option("_ga_acc_id"); ?>" name="_ga_acc_id" /></td>
<td align="right">

<input type="submit" value="Update" accesskey="p" tabindex="5" id="publish" class="button-primary" name="publish">
</td>
</tr>

</table>


</form>

</div>