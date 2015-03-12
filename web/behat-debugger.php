<?php
// auth check
if (strpos($_SERVER['SERVER_NAME'], '.local') === false) {
    http_response_code(404);
    header("HTTP/1.1 404 Not Found");die;
}

switch ($_GET['frame']) {
    case 'page':
        if (strpos($_GET['f'], 'behat-') !== false) {
            include  __DIR__ . '/../misc/tmp/' . $_GET['f'];
        } else {
            echo "click on a link at the top";
        }
        die;
        
    case 'list':
        foreach(['responses'=>'behat-response*.html', 'screenshots'=>'behat-screenshot*.html'] as $groupName => $regexpr) {
            ?><h2><?php echo $groupName ?></h2><?php
            $files = glob(__DIR__ . '/../misc/tmp/' . $regexpr);
            usort($files, function($a, $b) {
                return filemtime($a) < filemtime($b);
            });
            foreach ($files as $file) {
                $file = basename($file);
                $fileCleaned = str_replace(['behat-response-', 'behat-screenshot-', '.html'], '', $file);
                $group = explode('-', $fileCleaned, 2)[0];
                $newGroup = isset($previousGroup) && $previousGroup != $group;
                if ($newGroup) { echo "------<br>";}
                ?><a href="?frame=page&f=<?php echo $file?>" target="page" ><?php echo $fileCleaned ?></a><br/><?php
                $previousGroup = $group;
            }
        }
        die;
        
    default:
        ?>
         <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
            "http://www.w3.org/TR/html4/frameset.dtd">
        <HTML>
            <HEAD>
                <TITLE>behat debug</TITLE>
            </HEAD>
            <FRAMESET rows="150, *">
                <FRAME src="?frame=list" accesskey="l" />
                <FRAME src="?frame=page" accesskey="p" name="page"  />
            </FRAMESET>
        </HTML>
        <?php
        
}