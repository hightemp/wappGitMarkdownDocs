<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

function fnPath(...$aArguments)
{
    return join(DIRECTORY_SEPARATOR, $aArguments);
}

function fnRemoveDirectory($sDir) 
{ 
    $aFiles = array_diff(scandir($sDir), array('.','..'));
    foreach ($aFiles as $sFile) {
      (is_dir("$sDir/$sFile")) ? fnRemoveDirectory("$sDir/$sFile") : unlink("$sDir/$sFile"); 
    } 
    return rmdir($sDir); 
}

function fnGetRepositoryUserName($sRepositoryName)
{
    global $sRepositoriesDir;

    $sRepositoryDir = fnPath($sRepositoriesDir, $sRepositoryName);
    $sGitConfigFile = fnPath($sRepositoryDir, ".git", "config");

    $sGitConfigContents = file_get_contents($sGitConfigFile);
    
    if (preg_match("/url = (.*)$/m", $sGitConfigContents, $aMatches)) {
        $sURL = $aMatches[1];
        
        if (preg_match("/(\w+)\/\w+\.git/m", $sURL, $aMatches)) {
            return $aMatches[1];
        }
    }
}

function fnGetRepositoryInfo($sRepositoryName)
{
    global $sRepositoriesDir;
    
    $sRepositoryDir = fnPath($sRepositoriesDir, $sRepositoryName);
    $sTagsDir = fnPath($sRepositoryDir, "tags");
    $sArticlesDir = fnPath($sRepositoryDir, "articles");
    $sGitConfigFile = fnPath($sRepositoryDir, ".git", "config");
    
    $aResult = [
        'sName' => $sRepositoryName,
        'sURL' => '',
        'sUser' => '',
        'sPath' => $sRepositoryDir,
        'aArticles' => [],
        'oTags' => (object) []
    ];
    
    if (!is_dir($sTagsDir)) {
        mkdir($sTagsDir);
    }
    if (!is_dir($sArticlesDir)) {
        mkdir($sArticlesDir);
    }
    
    $sGitConfigContents = file_get_contents($sGitConfigFile);
    
    if (preg_match("/url = (.*)$/m", $sGitConfigContents, $aMatches)) {
        $aResult['sURL'] = $aMatches[1];
        
        if (preg_match("/(\w+)\/\w+\.git/m", $aResult['sURL'], $aMatches)) {
            $aResult['sUser'] = $aMatches[1];
        }
    }
    
    $aArticlesFiles = glob(fnPath($sArticlesDir, "*.md"));
    
    foreach ($aArticlesFiles as $sArticleFile) {
        $aResult['aArticles'][] = str_replace(".md", '', basename($sArticleFile));
    }
    
    $aTagsFiles = glob(fnPath($sTagsDir, "*.md"));
    
    foreach ($aTagsFiles as $sTagFile) {
        $sTagFileContents = file_get_contents($sTagFile);
        $sTag = str_replace(".md", '', basename($sTagFile));
        
        if (preg_match_all("/\[([^\]]+)\]/", $sTagFileContents, $aMatches)) {
            $aResult['oTags']->$sTag = $aMatches[1];
        } else {
            $aResult['oTags']->$sTag = [];
        }
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
                $aRepositories[] = fnGetRepositoryInfo(basename(dirname($sDir)));
            }
            
            $aResponse['data'] = $aRepositories;
        }
        
        if ($_POST['action']=='add_repository') {
            if (!preg_match("/git@github\.com:\w+\/(\w+)\.git/i", $_POST['url'], $aMatches)) {
                throw new Exception("Wrong repository url");
            }
            
            $sRepositoryName = $aMatches[1];

            if (is_dir(fnPath($sRepositoriesDir, $sRepositoryName))) {
                throw new Exception("Dir $sRepositoryName exists");
            }
            
            chdir($sRepositoriesDir);
            shell_exec('git clone '.$_POST['url']);
            
            $aResponse['data'] = fnGetRepositoryInfo($sRepositoryName);
        }
        
        if ($_POST['action']=='remove_repository') {
            fnRemoveDirectory(fnPath($sRepositoriesDir, $_POST['repository']));
        }
        
        if ($_POST['action']=='push_repository') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            
            if (isset($_POST['article'])) {
                $sArticlesDir = fnPath($sRepositoryDir, 'articles');
                $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
                
                if (file_put_contents($sArticleFile, @$_POST['data'])===false) {
                    throw new Exception("Can't write to file '$sArticleFile'");
                }
            }
            
            chdir($sRepositoryDir);
            
            shell_exec('git add .');
            shell_exec('git commit -am "'.date("d.m.Y").'"');
            shell_exec('git push origin master');
        }
        
        if ($_POST['action']=='load_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            
            $aResponse['data'] = file_get_contents($sArticleFile);
        }

        if ($_POST['action']=='save_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            
            if (file_put_contents($sArticleFile, @$_POST['data'])===false) {
                throw new Exception("Can't write to file '$sArticleFile'");
            }
        }
        
        if ($_POST['action']=='create_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            
            if (file_put_contents($sArticleFile, @$_POST['data'])===false) {
                throw new Exception("Can't write to file '$sArticleFile'");
            }
            
            if ($_POST['tag']!='__all__') {
                $sTagsDir = fnPath($sRepositoryDir, 'tags');
                $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
                
                $sArticleFileContents = file_get_contents($sArticleFile);
                $sTagFileContents = file_get_contents($sTagFile);
                
                if (($iLinePos = strpos($sArticleContents, "***\n"))===false) {
                    $sArticleFileContents .= "\n***\n";
                }
                
                $sArticleFileContents .= "[".$_POST['tag']."](/tags/".$_POST['tag'].".md)\n";
                $sTagFileContents .= "[".$_POST['article']."](/articles/".$_POST['article'].".md)\n";
                
                if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                    throw new Exception("Can't write to file '$aArticleFile'");
                }
                if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                    throw new Exception("Can't write to file '$sTagFile'");
                }
            }
        }
        
        if ($_POST['action']=='remove_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            
            unlink($sArticleFile);
            
            $aTagFiles = glob(fnPath($sTagsDir, "*.md"));
            $sArticleLink = "[".$_POST['article']."](/articles/".$_POST['article'].".md)\n";
            
            foreach ($aTagFiles as $sTagFile) {
                $sTagFileContents = file_get_contents($sTagFile);
                
                if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                    $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink));
                
                    if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                        throw new Exception("Can't write to file '$sTagFile'");
                    }
                }
            }
        }

        if ($_POST['action']=='create_tag') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            if (file_put_contents($sTagFile, '')===false) {
                throw new Exception("Can't write to file '$sTagFile'");
            }
        }
        
        if ($_POST['action']=='remove_tag') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            unlink($sTagFile);
            
            $aArticlesFiles = glob(fnPath($sArticlesDir, "*.md"));
            $sTagLink = "[".$_POST['tag']."](/tags/".$_POST['tag'].".md)\n";
            
            foreach ($aArticlesFiles as $aArticleFile) {
                $sArticleFileContents = file_get_contents($aArticleFile);
            
                if (($iLinePos = strpos($sArticleFileContents, "***\n"))!==false) {
                    if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                        $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink));
                    
                        if (file_put_contents($aArticleFile, $sArticleFileContents)===false) {
                            throw new Exception("Can't write to file '$aArticleFile'");
                        }
                    }
                }
            }
        }
        
        if ($_POST['action']=='add_tag_to_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            $sArticleFileContents = file_get_contents($sArticleFile);
            $sTagFileContents = file_get_contents($sTagFile);
            
            if (($iLinePos = strpos($sArticleContents, "***\n"))===false) {
                $sArticleFileContents .= "\n***\n";
            }
            
            $sArticleFileContents .= "[".$_POST['tag']."](/tags/".$_POST['tag'].".md)\n";
            $sTagFileContents .= "[".$_POST['article']."](/articles/".$_POST['article'].".md)\n";
            
            if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                throw new Exception("Can't write to file '$aArticleFile'");
            }
            if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                throw new Exception("Can't write to file '$sTagFile'");
            }
        }
        
        if ($_POST['action']=='remove_tag_from_article') {
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            $sArticleFileContents = file_get_contents($sArticleFile);
            $sTagFileContents = file_get_contents($sTagFile);
            
            $sTagLink = "[".$_POST['tag']."](/tags/".$_POST['tag'].".md)\n";
            $sArticleLink = "[".$_POST['article']."](/articles/".$_POST['article'].".md)\n";
            
            if (($iLinePos = strpos($sArticleFileContents, "***\n"))!==false) {
                if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                    $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink));
                
                    if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                        throw new Exception("Can't write to file '$aArticleFile'");
                    }
                }
            }
            if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink));
            
                if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                    throw new Exception("Can't write to file '$sTagFile'");
                }
            }
        }
        
        if ($_POST['action']=='get_article_page') {
            $sUser = fnGetRepositoryUserName($_POST['repository']);
            
            if (function_exists("curl_init")) {
                $resCURL = curl_init();

                curl_setopt($resCURL, CURLOPT_HEADER, 0);
                curl_setopt($resCURL, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($resCURL, CURLOPT_URL, "https://github.com/$sUser/{$_POST['repository']}/blob/master/articles/{$_POST['article']}.md");

                $sPageContents = curl_exec($resCURL);
                curl_close($resCURL);
            } else if (ini_get('allow_url_fopen')==1) {
                $sPageContents = file_get_contents("https://github.com/$sUser/{$_POST['repository']}/blob/master/articles/{$_POST['article']}.md");
            } else {
                throw new Exception("Can't get page due to disabled functions");
            }
            
            $aResponse['data'] = '';
            
            if (preg_match("/<article class=\"markdown-body entry-content p-3 p-md-6\" itemprop=\"text\">(.*?)<\/article>/s", $sPageContents, $aMatches)) {
                $aResponse['data'] = $aMatches[1];
                $aResponse['data'] = preg_replace("/(\/$sUser\/{$_POST['repository']}\/blob\/master\/.*?\.md)/i", "https://github.com$1", $aResponse['data']);
            }
        }
    } catch (Exception $oException) {
        $aResponse['status'] = 'error';
        $aResponse['message'] = $oException->getMessage();
        $aResponse['line'] = $oException->getLine();
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
