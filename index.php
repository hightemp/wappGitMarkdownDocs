<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

// UPDATE: /etc/php/7.2/cli/php.ini
// upload_max_filesize 1000000M
// post_max_size 1000000M

function fnPath(...$aArguments)
{
    return join(DIRECTORY_SEPARATOR, $aArguments);
}

function fnFileErrorCodeToMessage($iCode) 
{ 
    switch ($iCode) { 
        case UPLOAD_ERR_INI_SIZE: 
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
            break; 
        case UPLOAD_ERR_FORM_SIZE: 
            return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            break; 
        case UPLOAD_ERR_PARTIAL: 
            return "The uploaded file was only partially uploaded"; 
            break; 
        case UPLOAD_ERR_NO_FILE: 
            return "No file was uploaded"; 
            break; 
        case UPLOAD_ERR_NO_TMP_DIR: 
            return "Missing a temporary folder"; 
            break; 
        case UPLOAD_ERR_CANT_WRITE: 
            return "Failed to write file to disk"; 
            break; 
        case UPLOAD_ERR_EXTENSION: 
            return "File upload stopped by extension"; 
            break; 
    } 
} 

function fnHTTPRequest($sURL)
{
    $sResult = '';
    
    if (function_exists("curl_init")) {
        $resCURL = curl_init();

        curl_setopt($resCURL, CURLOPT_HEADER, 0);
        curl_setopt($resCURL, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($resCURL, CURLOPT_URL, $sURL);

        curl_setopt($resCURL, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        if (!empty(getenv('HTTPS_PROXY'))) {
            curl_setopt($resCURL, CURLOPT_PROXY, preg_replace("#https?://#i", "", getenv('HTTPS_PROXY')));
            curl_setopt($resCURL, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); 
        } else if (!empty(getenv('HTTP_PROXY'))) {
            curl_setopt($resCURL, CURLOPT_PROXY, preg_replace("#http://#i", "", getenv('HTTP_PROXY')));
            curl_setopt($resCURL, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        
        $sResult = curl_exec($resCURL);
        
        $iCURLError = curl_errno($resCURL);
        
        curl_close($resCURL);

        if ($iCURLError>0) {
            throw new Exception(curl_strerror($iCURLError));
        }        
    } else if (ini_get('allow_url_fopen')==1) {
        $sResult = safe_file_get_contents($sURL);
    } else {
        throw new Exception("Can't get page due to disabled functions");
    }
    
    return $sResult;
}

function safe_file_get_contents($sPath)
{
    $aContext = [];
    
    if (!empty(getenv('HTTP_PROXY'))) {    
        $aContext = [
            'http' => [
                'proxy' => str_replace("http", "tcp", getenv('HTTP_PROXY')),
                'request_fulluri' => true,
            ]
        ];
    }

    if (!empty(getenv('HTTPS_PROXY'))) {    
        $aContext = [
            'https' => [
                'proxy' => str_replace("https", "tcp", getenv('HTTPS_PROXY')),
                'request_fulluri' => true,
            ]
        ];
    }
    
    $resContext = stream_context_create($aContext);
    
    /*
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $sPath = str_replace(" ", "%20", $sPath);
        $sPath = str_replace("\\", "/", $sPath);
        
        if (strpos($sPath, "file:///")!==0) {
            $sPath = "file:///".$sPath;
        }
    }
    */
    return file_get_contents($sPath, false, $resContext);
}

function safe_glob($sPath)
{
    $aResult = glob($sPath);
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
        && substr(phpversion(), 0, 1)<7) {
        foreach ($aResult as &$rsItem) {
            $rsItem = @iconv("windows-1251", "UTF-8", $rsItem);
        }
    }
    
    return $aResult;
}

function fnCommitAndPushRepository($sRepositoryDir)
{
    chdir($sRepositoryDir);
    
    shell_exec('git add .');
    shell_exec('git commit -am "'.date("d.m.Y H:i:s").'"');
    shell_exec('git push origin master');
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
    
    chdir($sRepositoryDir);
    
    shell_exec("git pull");
    
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
    
    $aArticlesFiles = safe_glob(fnPath($sArticlesDir, "*.md"));
    
    foreach ($aArticlesFiles as $sArticleFile) {
        $aResult['aArticles'][] = str_replace(".md", '', basename($sArticleFile));
    }
    
    $aTagsFiles = safe_glob(fnPath($sTagsDir, "*.md"));
    
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
        if (!isset($_POST['action'])) {
            throw new Exception("'action' is not set");
        }
        
        if ($_POST['action']=='get_repositories') {
            if (!is_dir($sRepositoriesDir)) {
                mkdir($sRepositoriesDir);
            }
            if (!is_dir($sRepositoriesDir)) {
                throw new Exception("Can't create dir");
            }
            
            $aGitDirectories = safe_glob(fnPath($sRepositoriesDir, "*", ".git"));
            $aRepositories = [];
            
            foreach ($aGitDirectories as $sDir) {
                $aRepositories[] = fnGetRepositoryInfo(basename(dirname($sDir)));
            }
            
            $aResponse['data'] = $aRepositories;
        }
        
        if ($_POST['action']=='add_repository') {
            if (!preg_match("/(git@github\.com:|https:\/\/github\.com\/)\w+\/(\w+)\.git/i", $_POST['url'], $aMatches)) {
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
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            fnRemoveDirectory(fnPath($sRepositoriesDir, $_POST['repository']));
        }
        
        if ($_POST['action']=='push_repository') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            
            if (isset($_POST['article'])) {
                $sArticlesDir = fnPath($sRepositoryDir, 'articles');
                $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
                
                if (isset($_POST['tags'])) {
                    $_POST['data'] .= "\n**********\n";
                    
                    foreach ($_POST['tags'] as $sTag) {
                        $_POST['data'] .= "[$sTag](/tags/".rawurlencode($sTag).".md)\n";
                    }
                }
                
                if (file_put_contents($sArticleFile, @$_POST['data'])===false) {
                    throw new Exception("Can't write to file '$sArticleFile'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='load_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            
            $aResponse['data'] = safe_file_get_contents($sArticleFile);            
            $aResponse['data'] = preg_replace("/\n\*\*\*\*\*\*\*\*\*\*.*$/s", '', $aResponse['data']);
        }

        if ($_POST['action']=='save_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            
            if (file_put_contents($sArticleFile, @$_POST['data'])===false) {
                throw new Exception("Can't write to file '$sArticleFile'");
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='rename_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sFromArticleFile = fnPath($sArticlesDir, $_POST['from_article'].'.md');
            $sToArticleFile = fnPath($sArticlesDir, $_POST['to_article'].'.md');
            
            $sFromArticleLink = "[".$_POST['from_article']."](/articles/".rawurlencode($_POST['from_article']).".md)";
            $sToArticleLink = "[".$_POST['to_article']."](/articles/".rawurlencode($_POST['to_article']).".md)";
            
            if (!rename($sFromArticleFile, $sToArticleFile)) {
                throw new Exception("Can't rename file");
            }
            
            foreach ($_POST['tags'] as $sTag) {
                $sTagFile = fnPath($sTagsDir, $sTag.".md");
                $sTagFileContents = file_get_contents($sTagFile);
                
                $sTagFileContents = str_replace($sFromArticleLink, $sToArticleLink, $sTagFileContents);
                
                if (!file_put_contents($sTagFile, $sTagFileContents)) {
                    throw new Exception("Can't write to file");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='create_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
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
                
                if (($iLinePos = strpos($sArticleContents, "**********"))===false) {
                    $sArticleFileContents .= "\n**********\n";
                }
                
                $sArticleFileContents .= "[".$_POST['tag']."](/tags/".rawurlencode($_POST['tag']).".md)\n";
                $sTagFileContents .= "[".$_POST['article']."](/articles/".rawurlencode($_POST['article']).".md)\n";
                
                if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                    throw new Exception("Can't write to file '$aArticleFile'");
                }
                if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                    throw new Exception("Can't write to file '$sTagFile'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='remove_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            
            unlink($sArticleFile);
            
            $aTagFiles = safe_glob(fnPath($sTagsDir, "*.md"));
            $sArticleLink = "[".$_POST['article']."](/articles/".rawurlencode($_POST['article']).".md)";
            
            foreach ($aTagFiles as $sTagFile) {
                $sTagFileContents = file_get_contents($sTagFile);
                
                if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                    $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink)+1);
                
                    if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                        throw new Exception("Can't write to file '$sTagFile'");
                    }
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }

        if ($_POST['action']=='create_tag') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            if (file_put_contents($sTagFile, '')===false) {
                throw new Exception("Can't write to file '$sTagFile'");
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='rename_tag') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sFromTagFile = fnPath($sTagsDir, $_POST['from_tag'].'.md');
            $sToTagFile = fnPath($sTagsDir, $_POST['to_tag'].'.md');
            
            $sFromTagLink = "[".$_POST['from_tag']."](/tags/".rawurlencode($_POST['from_tag']).".md)";
            $sToTagLink = "[".$_POST['to_tag']."](/tags/".rawurlencode($_POST['to_tag']).".md)";
            
            if (!rename($sFromTagFile, $sToTagFile)) {
                throw new Exception("Can't rename file");
            }
            
            foreach ($_POST['articles'] as $sArticle) {
                $sArticleFile = fnPath($sArticlesDir, $sArticle.".md");
                $sArticleFileContents = file_get_contents($sArticleFile);
                
                $sArticleFileContents = str_replace($sFromTagLink, $sToTagLink, $sArticleFileContents);
                
                if (!file_put_contents($sArticleFile, $sArticleFileContents)) {
                    throw new Exception("Can't write to file");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='remove_tag') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            unlink($sTagFile);
            
            $aArticlesFiles = safe_glob(fnPath($sArticlesDir, "*.md"));
            $sTagLink = "[".$_POST['tag']."](/tags/".rawurlencode($_POST['tag']).".md)";
            
            foreach ($aArticlesFiles as $sArticleFile) {
                $sArticleFileContents = file_get_contents($sArticleFile);
            
                if (($iLinePos = strpos($sArticleFileContents, "**********"))!==false) {
                    if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                        $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink)+1);
                    
                        if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                            throw new Exception("Can't write to file '$sArticleFile'");
                        }
                    }
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='add_tag_to_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            $sArticleFileContents = file_get_contents($sArticleFile);
            $sTagFileContents = file_get_contents($sTagFile);
            
            if (($iLinePos = strpos($sArticleFileContents, "**********"))===false) {
                $sArticleFileContents .= "\n**********\n";
            }
            
            $sArticleFileContents .= "[".$_POST['tag']."](/tags/".rawurlencode($_POST['tag']).".md)\n";
            $sTagFileContents .= "[".$_POST['article']."](/articles/".rawurlencode($_POST['article']).".md)\n";
            
            if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                throw new Exception("Can't write to file '$aArticleFile'");
            }
            if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                throw new Exception("Can't write to file '$sTagFile'");
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='remove_tag_from_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, $_POST['article'].'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, $_POST['tag'].'.md');
            
            $sArticleFileContents = file_get_contents($sArticleFile);
            $sTagFileContents = file_get_contents($sTagFile);
            
            $sTagLink = "[".$_POST['tag']."](/tags/".rawurlencode($_POST['tag']).".md)";
            $sArticleLink = "[".$_POST['article']."](/articles/".rawurlencode($_POST['article']).".md)";
            
            if (($iLinePos = strpos($sArticleFileContents, "**********"))!==false) {
                if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                    $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink)+1);
                
                    if (file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                        throw new Exception("Can't write to file '$aArticleFile'");
                    }
                }
            }
            if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink)+1);
            
                if (file_put_contents($sTagFile, $sTagFileContents)===false) {
                    throw new Exception("Can't write to file '$sTagFile'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='search_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            
            $aInfo = fnGetRepositoryInfo($_POST['repository']);
            
            $aResponse['data'] = [];
            
            if (!empty($_POST['search_text'])) {
                if ($_POST['tag']=='__all__') {
                    foreach ($aInfo['aArticles'] as $sArticle) {
                        $sArticleFile = fnPath($sArticlesDir, $sArticle.'.md');
                        
                        $sConents = file_get_contents($sArticleFile);
                        
                        if (@strpos($sConents, $_POST['search_text'])!==false) {
                            $aResponse['data'] = $sArticle;
                        }
                    }
                } else {
                    foreach ($aInfo['oTags']->{$_POST['tag']} as $sArticle) {
                        $sArticleFile = fnPath($sArticlesDir, $sArticle.'.md');
                    
                        $sConents = file_get_contents($sArticleFile);
                        
                        if (@strpos($sConents, $_POST['search_text'])!==false) {
                            $aResponse['data'] = $sArticle;
                        }
                    }
                }
            }
        }
        
        if ($_POST['action']=='get_article_page') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sUser = fnGetRepositoryUserName($_POST['repository']);
            //$sArticle = str_replace(' ', '%20', $_POST['article']);
            $sArticle = rawurlencode($_POST['article']);
            
            $sPageContents = fnHTTPRequest("https://github.com/$sUser/{$_POST['repository']}/blob/master/articles/{$sArticle}.md");
            
            $aResponse['data'] = '';
            
            if (preg_match("/<article class=\"markdown-body entry-content p-3 p-md-6\" itemprop=\"text\">(.*?)<\/article>/s", $sPageContents, $aMatches)) {
                $aResponse['data'] = $aMatches[1];
                $aResponse['data'] = preg_replace("/(\/$sUser\/{$_POST['repository']}\/(blob|raw)\/master\/.*?[\"'])/i", "https://github.com$1", $aResponse['data']);
            }
        }
        
        if ($_POST['action']=='upload_images') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sImagesDir = fnPath($sRepositoryDir, 'images');
            
            if (!is_dir($sImagesDir)) {
                mkdir($sImagesDir);
            }
            if (!is_dir($sImagesDir)) {
                throw new Exception("Can't create dir");
            }
            
            if (!isset($_FILES['files'])) {
                throw new Exception("There is no file in request");
            }
            
            $aResponse['data'] = [];
            
            foreach ($_FILES['files']['name'] as $iIndex => $sName) {
                $sImagesFile = fnPath($sImagesDir, $sName);
                
                $sErrorMessage = fnFileErrorCodeToMessage($_FILES['files']['error'][$iIndex]);
                if (!empty($sErrorMessage)) {
                    throw new Exception($sErrorMessage);
                }
                
                //if (!is_file($sImagesFile)) {
                    if (!move_uploaded_file($_FILES['files']['tmp_name'][$iIndex], $sImagesFile)) {
                        throw new Exception("Can't move file to directory '$sImagesFile'");
                    }
                //}
                
                $aResponse['data'][] = str_replace($sRepositoryDir, '', $sImagesFile);
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='upload_files') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sFilesDir = fnPath($sRepositoryDir, 'files');
            
            if (!is_dir($sFilesDir)) {
                mkdir($sFilesDir);
            }
            if (!is_dir($sFilesDir)) {
                throw new Exception("Can't create dir");
            }
            
            if (!isset($_FILES['files'])) {
                throw new Exception("There is no file in request");
            }
            
            $aResponse['data'] = [];
            
            foreach ($_FILES['files']['name'] as $iIndex => $sName) {
                $sFile = fnPath($sFilesDir, $sName);
                
                $sErrorMessage = fnFileErrorCodeToMessage($_FILES['files']['error'][$iIndex]);
                if (!empty($sErrorMessage)) {
                    throw new Exception($sErrorMessage);
                }
                
                //if (!is_file($sFile)) {
                    if (!move_uploaded_file($_FILES['files']['tmp_name'][$iIndex], $sFile)) {
                        throw new Exception("Can't move file to directory");
                    }
                //}
                
                $aResponse['data'][] = str_replace($sRepositoryDir, '', $sFile);
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='get_images') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sImagesDir = fnPath($sRepositoryDir, 'images');
            
            $aResponse['data'] = safe_glob(fnPath($sImagesDir, "*"));
            
            foreach ($aResponse['data'] as &$sImage) {
                //$sImage = str_replace($sRepositoryDir, '', $sImage);
                $sImage = basename($sImage);
            }
        }
        if ($_POST['action']=='remove_images') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sImagesDir = fnPath($sRepositoryDir, 'images');
            
            foreach ($_POST['files'] as $sImage) {
                if (!unlink(fnPath($sImagesDir, $sImage))) {
                    throw new Exception("Can't delete file '$sImage'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='get_files') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sFilesDir = fnPath($sRepositoryDir, 'files');
            
            $aResponse['data'] = safe_glob(fnPath($sFilesDir, "*"));
            
            foreach ($aResponse['data'] as &$sFile) {
                $sFile = basename($sFile);
            }
        }
        if ($_POST['action']=='remove_files') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sFilesDir = fnPath($sRepositoryDir, 'files');
            
            foreach ($_POST['files'] as $sFile) {
                if (!unlink(fnPath($sFilesDir, $sFile))) {
                    throw new Exception("Can't delete file '$sFile'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
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
