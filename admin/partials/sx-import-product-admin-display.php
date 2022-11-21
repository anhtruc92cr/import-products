<?php
if (isset($_POST["mail-submit"]) && !empty($_POST["emails"])) {
    update_option('sx-import-emails', $_POST["emails"]);
}

if (isset($_POST["number-submit"]) && !empty($_POST["number-import"])) {
    update_option('sx-import-number', $_POST["number-import"]);
}
$emails = get_option('sx-import-emails');
$number = get_option('sx-import-number');
?>
<h1>Import Products from XML file</h1>
<h4>Note: Execute time maybe turns timout if there are too many data in XML file (except "Run now").</h4>
<div class="btn-wrapper">
    <?php if (isset($_GET['debug'])) : ?>
    <button class="btn-import btn-import-all" value="1"/>
    Import all!</button>
    <button class="btn-import btn-import-cat" value="2"/>
    Import to database</button>
    <?php endif; ?>
    <?php echo empty($cron_url) ?: $cron_url; ?>
</div>
<br>
<br>
<div class="receiver-setting">
    <h3>Emails: (separate by comma)</h3>
    <form class="submit-emails" method="post">
        <input type="text" placeholder="Emails" value="<?php echo $emails; ?>" name="emails"/>
        <input type="submit" name="mail-submit" value="Save"/>
    </form>
</div>
<div class="receiver-setting">
    <h3>Number/time to importing</h3>
    <form class="submit-emails" method="post">
        <input type="text" placeholder="Number" value="<?php echo $number; ?>" name="number-import"/>
        <input type="submit" name="number-submit" value="Save"/>
    </form>
</div>