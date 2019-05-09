<?php

function fnPath(...$aArguments)
{
    return join(DIRECTORY_SEPARATOR, $aArguments);
}

function fnGetRepositoryInfo($sRepositoryName)
{
    global $sRepositoriesDir;
    
    $sRepositoryDir = fnPath($sRepositoriesDir, $sRepositoryName);
    $sRepositoryTagDir = fnPath($sRepositoryDir, "tags");
    $sRepositoryArticlesDir = fnPath($sRepositoryDir, "articles");
    
    $aResult = [
        'sName' => $sRepositoryName,
        'sPath' => $sRepositoryDir,
        'aArticles' => [],
        'oTags' => []
    ];
    
    if (!is_dir($sRepositoryTagDir)) {
        mkdir($sRepositoryTagDir);
    }
    if (!is_dir($sRepositoryArticlesDir)) {
        mkdir($sRepositoryArticlesDir);
    }
    
    
    
    return $aResult;
}

$sRepositoriesDir = fnPath(__DIR__, 'repositories');

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $aResponse = [
        'status' => 'ok', 
        'message' => '',
        'data' => []
    ];
    
    try {
        if ($_POST['action']=='get_repositories') {
            if (!is_dir($sRepositoriesDir)) {
                mkdir($sRepositoriesDir);
            }
            if (!is_dir($sRepositoriesDir)) {
                throw new Exception("Can't create dir");
            }
            
            $aGitDirectories = glob(fnPath($sRepositoriesDir, "*", ".git"));
            $aRepositories = [];
            
            foreach ($aGitDirectories as $sDir) {
                $aRepositories[] = fnGetRepositoryInfo($sDir);
            }
            
            $aResponse['data'] = $aRepositories;
        }
        if ($_POST['action']=='add_repository') {
            chdir($sRepositoriesDir);
            shell_exec('git clone '.$_POST['url']);
        }
    } catch (Exception $oException) {
        $aResponse['status'] = 'error';
        $aResponse['message'] = $oException->getLine().": ".$oException->getMessage();
    }
    
    die(json_encode($aResponse));
}

?>

<!doctype html>
<html lang="">

<head>
    <meta charset="utf-8">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>

<body>
    <div id="application"></div>
    <script src="dist/main.js" type="text/javascript"></script>
</body>

</html>
