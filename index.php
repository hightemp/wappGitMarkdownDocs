<?php

ini_set("max_execution_time", 0);
setlocale(LC_ALL, 'C.UTF-8');
//ini_set("display_errors", 1);
//error_reporting(E_ALL);
ini_set("display_errors", 0);
error_reporting(E_ERROR);

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

$aEntities =     ['%3A', '%2A', '%27', '%2F', '%3F', '%60', '%7C', '%3C', '%3E', '%30', '%26'];
$aReplacements = [':',   '*',   "'",   "/",   "?",   "`",   "|",   "<",   ">",   "\\",  "\""];

function fnFileNameEncode($sString) 
{
    global $aEntities;
    global $aReplacements;
    return str_replace($aReplacements, $aEntities, $sString);
}

function fnFileNameDecode($sString) 
{
    global $aEntities;
    global $aReplacements;
    return str_replace($aEntities, $aReplacements, $sString);
}

function fnEscapeFileName($sFileName)
{
    $sFileName = str_replace("/", "_", $sFileName);
    $sFileName = str_replace("\\", "_", $sFileName);
    $sFileName = str_replace("*", "_", $sFileName);
    $sFileName = str_replace(":", "_", $sFileName);
    $sFileName = str_replace("|", "_", $sFileName);
    $sFileName = str_replace("\"", "_", $sFileName);
    $sFileName = str_replace("<", "_", $sFileName);
    $sFileName = str_replace(">", "_", $sFileName);
    
    return $sFileName;
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
        $sResult = http_file_get_contents($sURL);
    } else {
        throw new Exception("Can't get page due to disabled functions");
    }
    
    return $sResult;
}

function safe_file_put_contents($sPath, $sData)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
        && substr(phpversion(), 0, 1)<7) {        
        
        $sPath = @iconv("UTF-8", "windows-1251", $sPath);
        
        $sEscapedPath = str_replace(__DIR__."\\", '', $sPath);
        $sEscapedPath = str_replace("\\", '/', $sEscapedPath);
        $sEscapedPath = str_replace(" ", "\\ ", $sEscapedPath);
        
        $sCurrentDir = str_replace("\n", '', shell_exec("pwd"));
        
        if (empty($sData)) {
            shell_exec("touch '$sEscapedPath'");
        } else {
            file_put_contents("temp", $sData);
            shell_exec("cat temp > $sEscapedPath");
            unlink('temp');
        }
        
        return is_file($sPath);
    }
    
    return file_put_contents($sPath, $sData);
}

function safe_file_get_contents($sPath)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
        && substr(phpversion(), 0, 1)<7) {        
        
        $sPath = @iconv("UTF-8", "windows-1251", $sPath);
        
        return shell_exec("cat '$sPath'");
    }
    
    return file_get_contents($sPath);
}

function http_file_get_contents($sPath)
{
    $aContext = [];
    
    if (!empty(getenv('HTTP_PROXY'))) {    
        $aContext['http'] = [
            'proxy' => str_replace("http", "tcp", getenv('HTTP_PROXY')),
            'request_fulluri' => true,
        ];
    }

    if (!empty(getenv('HTTPS_PROXY'))) {    
        $aContext['https'] = [
            'proxy' => str_replace("https", "tcp", getenv('HTTPS_PROXY')),
            'request_fulluri' => true,
        ];
        $aContext['ssl'] = [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ];
    }
    
    $resContext = stream_context_create($aContext);
    
    return file_get_contents($sPath, false, $resContext);
}

function safe_glob($sPath)
{
    $aResult = glob($sPath);
    
    array_multisort(array_map('filemtime', $aResult), SORT_NUMERIC, SORT_ASC, $aResult);
    
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

    $sGitConfigContents = safe_file_get_contents($sGitConfigFile);
    
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
    
    $sGitConfigContents = safe_file_get_contents($sGitConfigFile);
    
    if (preg_match("/url = (.*)$/m", $sGitConfigContents, $aMatches)) {
        $aResult['sURL'] = $aMatches[1];
        
        if (preg_match("/(\w+)\/\w+\.git/m", $aResult['sURL'], $aMatches)) {
            $aResult['sUser'] = $aMatches[1];
        }
    }
    
    $aArticlesFiles = safe_glob(fnPath($sArticlesDir, "*.md"));
    
    foreach ($aArticlesFiles as $sArticleFile) {
        $aResult['aArticles'][] = fnFileNameDecode(str_replace(".md", '', basename($sArticleFile)));
    }
    
    $aTagsFiles = safe_glob(fnPath($sTagsDir, "*.md"));
    
    foreach ($aTagsFiles as $sTagFile) {
        $sTagFileContents = safe_file_get_contents($sTagFile);
        $sTag = fnFileNameDecode(str_replace(".md", '', basename($sTagFile)));
        
        if (preg_match_all("/^\[.*?\]\(.*?\)/m", $sTagFileContents, $aMatches)) {
            foreach ($aMatches[0] as $sItem) {
                $sTagFileContents = str_replace($sItem, "* ".$sItem, $sTagFileContents);
            }
            
            safe_file_put_contents($sTagFile, $sTagFileContents);
        }
        
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
            
            $sRepositoryName = $aMatches[2];

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
                $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
                
                if (isset($_POST['tags'])) {
                    $_POST['data'] .= "\n**********\n";
                    
                    foreach ($_POST['tags'] as $sTag) {
                        $_POST['data'] .= "[$sTag](/tags/".rawurlencode(fnFileNameEncode($sTag)).".md)\n";
                    }
                }
                
                if (safe_file_put_contents($sArticleFile, @$_POST['data'])===false) {
                    throw new Exception("Can't write to file '$sArticleFile'");
                }
            }
            
            // README.md            
            $sReadmeFile = fnPath($sRepositoryDir, "README.md");
            
            $aRepositoryInfo = fnGetRepositoryInfo($_POST['repository']);
            
            $sReadmeOutput = "# ".$aRepositoryInfo['sName']."\n";
            
            foreach ($aRepositoryInfo['aArticles'] as $sArticle) {
                $sReadmeOutput .= "* [$sArticle](/articles/".rawurlencode($sArticle).".md)\n";
            }
            
            $sReadmeOutput .= "---\n";
            
            foreach ($aRepositoryInfo['oTags'] as $sTag => $aArticles) {
                $sReadmeOutput .= "[$sTag](/tags/".rawurlencode($sTag).".md)\n";
            }
            
            safe_file_put_contents($sReadmeFile, $sReadmeOutput);
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        
        if ($_POST['action']=='load_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            
            $aResponse['data'] = safe_file_get_contents($sArticleFile);            
            $aResponse['data'] = preg_replace("/\n\*\*\*\*\*\*\*\*\*\*.*$/s", '', $aResponse['data']);
        }

        if ($_POST['action']=='save_article') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sArticlesDir = fnPath($sRepositoryDir, 'articles');
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            
            if (safe_file_put_contents($sArticleFile, @$_POST['data'])===false) {
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
            $sFromArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['from_article']).'.md');
            $sToArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['to_article']).'.md');
            
            $sFromArticleLink = "[".$_POST['from_article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['from_article'])).".md)";
            $sToArticleLink = "[".$_POST['to_article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['to_article'])).".md)";
            
            if (!rename($sFromArticleFile, $sToArticleFile)) {
                throw new Exception("Can't rename file");
            }
            
            foreach ($_POST['tags'] as $sTag) {
                $sTagFile = fnPath($sTagsDir, fnFileNameEncode($sTag).".md");
                $sTagFileContents = safe_file_get_contents($sTagFile);
                
                $sTagFileContents = str_replace($sFromArticleLink, $sToArticleLink, $sTagFileContents);
                
                if (!safe_file_put_contents($sTagFile, $sTagFileContents)) {
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
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            
            if (safe_file_put_contents($sArticleFile, @$_POST['data'])===false) {
                throw new Exception("Can't write to file '$sArticleFile'");
            }
            
            if ($_POST['tag']!='__all__') {
                $sTagsDir = fnPath($sRepositoryDir, 'tags');
                $sTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['tag']).'.md');
                
                $sArticleFileContents = safe_file_get_contents($sArticleFile);
                $sTagFileContents = safe_file_get_contents($sTagFile);
                
                if (($iLinePos = strpos($sArticleContents, "**********"))===false) {
                    $sArticleFileContents .= "\n**********\n";
                }
                
                $sArticleFileContents .= "[".$_POST['tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['tag'])).".md)\n";
                $sTagFileContents .= "[".$_POST['article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['article'])).".md)\n";
                
                if (safe_file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                    throw new Exception("Can't write to file '$aArticleFile'");
                }
                if (safe_file_put_contents($sTagFile, $sTagFileContents)===false) {
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
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            
            unlink($sArticleFile);
            
            $aTagFiles = safe_glob(fnPath($sTagsDir, "*.md"));
            $sArticleLink = "[".$_POST['article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['article'])).".md)";
            
            foreach ($aTagFiles as $sTagFile) {
                $sTagFileContents = safe_file_get_contents($sTagFile);
                
                if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                    $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink)+1);
                
                    if (safe_file_put_contents($sTagFile, $sTagFileContents)===false) {
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
            $sTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['tag']).'.md');
            
            if (safe_file_put_contents($sTagFile, '')===false) {
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
            $sFromTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['from_tag']).'.md');
            $sToTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['to_tag']).'.md');
            
            $sFromTagLink = "[".$_POST['from_tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['from_tag'])).".md)";
            $sToTagLink = "[".$_POST['to_tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['to_tag'])).".md)";
            
            if (!rename($sFromTagFile, $sToTagFile)) {
                throw new Exception("Can't rename file");
            }
            
            foreach ($_POST['articles'] as $sArticle) {
                $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($sArticle).".md");
                $sArticleFileContents = safe_file_get_contents($sArticleFile);
                
                $sArticleFileContents = str_replace($sFromTagLink, $sToTagLink, $sArticleFileContents);
                
                if (!safe_file_put_contents($sArticleFile, $sArticleFileContents)) {
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
            $sTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['tag']).'.md');
            
            unlink($sTagFile);
            
            $aArticlesFiles = safe_glob(fnPath($sArticlesDir, "*.md"));
            $sTagLink = "[".$_POST['tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['tag'])).".md)";
            
            foreach ($aArticlesFiles as $sArticleFile) {
                $sArticleFileContents = safe_file_get_contents($sArticleFile);
            
                if (($iLinePos = strpos($sArticleFileContents, "**********"))!==false) {
                    if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                        $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink)+1);
                    
                        if (safe_file_put_contents($sArticleFile, $sArticleFileContents)===false) {
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
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['tag']).'.md');
            
            $sArticleFileContents = safe_file_get_contents($sArticleFile);
            $sTagFileContents = safe_file_get_contents($sTagFile);
            
            if (($iLinePos = strpos($sArticleFileContents, "**********"))===false) {
                $sArticleFileContents .= "\n**********\n";
            }
            
            $sArticleFileContents .= "[".$_POST['tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['tag'])).".md)\n";
            $sTagFileContents .= "* [".$_POST['article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['article'])).".md)\n";
            
            if (safe_file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                throw new Exception("Can't write to file '$aArticleFile'");
            }
            if (safe_file_put_contents($sTagFile, $sTagFileContents)===false) {
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
            $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($_POST['article']).'.md');
            $sTagsDir = fnPath($sRepositoryDir, 'tags');
            $sTagFile = fnPath($sTagsDir, fnFileNameEncode($_POST['tag']).'.md');
            
            $sArticleFileContents = safe_file_get_contents($sArticleFile);
            $sTagFileContents = safe_file_get_contents($sTagFile);
            
            $sTagLink = "[".$_POST['tag']."](/tags/".rawurlencode(fnFileNameEncode($_POST['tag'])).".md)";
            $sArticleLink = "* [".$_POST['article']."](/articles/".rawurlencode(fnFileNameEncode($_POST['article'])).".md)";
            
            if (($iLinePos = strpos($sArticleFileContents, "**********"))!==false) {
                if (($iLinkPos = strpos($sArticleFileContents, $sTagLink, $iLinePos))!==false) {
                    $sArticleFileContents = substr_replace($sArticleFileContents, '', $iLinkPos, strlen($sTagLink)+1);
                
                    if (safe_file_put_contents($sArticleFile, $sArticleFileContents)===false) {
                        throw new Exception("Can't write to file '$aArticleFile'");
                    }
                }
            }
            if (($iLinkPos = strpos($sTagFileContents, $sArticleLink))!==false) {
                $sTagFileContents = substr_replace($sTagFileContents, '', $iLinkPos, strlen($sArticleLink)+1);
            
                if (safe_file_put_contents($sTagFile, $sTagFileContents)===false) {
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
                        $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($sArticle).'.md');
                        
                        $sConents = safe_file_get_contents($sArticleFile);
                        
                        if (@strpos($sConents, $_POST['search_text'])!==false) {
                            $aResponse['data'] = $sArticle;
                        }
                    }
                } else {
                    foreach ($aInfo['oTags']->{$_POST['tag']} as $sArticle) {
                        $sArticleFile = fnPath($sArticlesDir, fnFileNameEncode($sArticle).'.md');
                    
                        $sConents = safe_file_get_contents($sArticleFile);
                        
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
            $sArticle = rawurlencode(fnFileNameEncode($_POST['article']));
            
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
            
            if (!isset($_FILES['files']) && !isset($_FILES['pasted_files'])) {
                throw new Exception("There is no file in request");
            }
            
            $aResponse['data'] = [];
            
            foreach ((array)@$_FILES['pasted_files']['name'] as $iIndex => $sName) {
                $sNewFileName = md5_file($_FILES['pasted_files']['tmp_name'][$iIndex]);
                $sImageFile = fnPath($sImagesDir, $sNewFileName);
                
                $sErrorMessage = fnFileErrorCodeToMessage($_FILES['pasted_files']['error'][$iIndex]);
                if (!empty($sErrorMessage)) {
                    throw new Exception($sErrorMessage);
                }
                
                if (!move_uploaded_file($_FILES['pasted_files']['tmp_name'][$iIndex], $sImageFile)) {
                    throw new Exception("Can't move file to directory '$sImageFile'");
                }
                
                $aResponse['data'][] = $sNewFileName;                
            }
            
            foreach ((array)@$_FILES['files']['name'] as $iIndex => $sName) {
                $sImageFile = fnPath($sImagesDir, fnFileNameEncode($sName));
                
                $sErrorMessage = fnFileErrorCodeToMessage($_FILES['files']['error'][$iIndex]);
                if (!empty($sErrorMessage)) {
                    throw new Exception($sErrorMessage);
                }
                
                if (!move_uploaded_file($_FILES['files']['tmp_name'][$iIndex], $sImageFile)) {
                    throw new Exception("Can't move file to directory '$sImageFile'");
                }
                
                $aResponse['data'][] = $sName;
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        if ($_POST['action']=='add_images_from_urls') {
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
            
            $sFileNames = [];
            
            foreach ((array)$_POST['urls'] as $sURL) {
                $sFileName = basename($sURL);
                $aFileInfo = pathinfo($sURL);

                if (empty($sFileName)) {
                    throw new Exception("File name is empty");
                }

                $sData = fnHTTPRequest($sURL);
                $sImagesFile = fnPath($sImagesDir, fnFileNameEncode($sFileName));

                if (!safe_file_put_contents($sImagesFile, $sData)) {
                    throw new Exception("Can't write to file '$sImagesFile'");
                }
                
                $sFileNames[] = $sFileName;
            }
            
            $aResponse['data'] = $sFileNames;
                        
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
            
            foreach ((array)@$_FILES['files']['name'] as $iIndex => $sName) {
                $sFile = fnPath($sFilesDir, fnFileNameEncode($sName));
                
                $sErrorMessage = fnFileErrorCodeToMessage($_FILES['files']['error'][$iIndex]);
                if (!empty($sErrorMessage)) {
                    throw new Exception($sErrorMessage);
                }
                
                //if (!is_file($sFile)) {
                    if (!move_uploaded_file($_FILES['files']['tmp_name'][$iIndex], $sFile)) {
                        throw new Exception("Can't move file to directory");
                    }
                //}
                
                //$aResponse['data'][] = str_replace($sRepositoryDir, '', $sFile);
                $aResponse['data'][] = $sName;
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        if ($_POST['action']=='add_files_from_urls') {
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
            
            $sFileNames = [];
            
            foreach ((array)$_POST['urls'] as $sURL) {
                $sFileName = basename($sURL);
                $aFileInfo = pathinfo($sURL);

                if (empty($sFileName)) {
                    throw new Exception("File name is empty");
                }

                $sData = fnHTTPRequest($sURL);
                $sFilePath = fnPath($sFilesDir, fnFileNameEncode($sFileName));

                if (!safe_file_put_contents($sFilePath, $sData)) {
                    throw new Exception("Can't write to file '$sFilePath'");
                }
                
                $sFileNames[] = $sFileName;
            }
            
            $aResponse['data'] = $sFileNames;
                        
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
                $sImage = fnFileNameDecode(basename($sImage));
            }
        }
        if ($_POST['action']=='remove_images') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sImagesDir = fnPath($sRepositoryDir, 'images');
            
            foreach ($_POST['files'] as $sImage) {
                $sImageFilePath = fnPath($sImagesDir, fnFileNameEncode($sImage));
                
                if (!unlink($sImageFilePath)) {
                    throw new Exception("Can't delete file '$sImageFilePath'");
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
                $sFile = fnFileNameDecode(basename($sFile));
            }
        }
        if ($_POST['action']=='remove_files') {
            if (empty($_POST['repository'])) {
                throw new Exception("Empty repository name");
            }
            
            $sRepositoryDir = fnPath($sRepositoriesDir, $_POST['repository']);
            $sFilesDir = fnPath($sRepositoryDir, 'files');
            
            foreach ($_POST['files'] as $sFile) {
                $sFilePath = fnPath($sFilesDir, fnFileNameEncode($sFile));
                    
                if (!unlink($sFilePath)) {
                    throw new Exception("Can't delete file '$sFilePath'");
                }
            }
            
            fnCommitAndPushRepository($sRepositoryDir);
        }
        if ($_POST['action']=='translate_text') {
            if ($_POST['provider']=="google") {
                $aParameters = http_build_query([
                    "hl" => 'en',
                    "sl" => $_POST['from_laguage'],
                    "tl" => $_POST['to_language'],
                    "q"  => $_POST['text'],
                ]);
                
                $sData = fnHTTPRequest("https://translate.googleapis.com/translate_a/single?client=gtx&dt=t&dt=bd&dt=rm&".$aParameters);
                $aData = json_decode($sData, true);
                
                $aResponse['data'] = '';
                
                foreach((array) @$aData[0] as $aBlock) {
                    if ($aBlock[0]) {
                        $aResponse['data'] .= $aBlock[0];
                    }
                }
            } elseif($_POST['provider']=="yandex") {
                if ($_POST['from_laguage']=='auto') {
                    $aLangaugeParameters = http_build_query([
                        "text"  => $_POST['text'],
                    ]);
                    
                    $sLanguageData = fnHTTPRequest("https://translate.yandex.net/api/v1/tr.json/detect?srv=yawidget&".$aLangaugeParameters);
                    $aLanguageData = json_decode($sLanguageData, true);
                
                    if (!isset($aLanguageData['lang'])) {
                        throw new Exception("Language can't be detected");
                    }
                    
                    $_POST['from_laguage'] = $aLanguageData['lang'];
                }
                
                $aParameters = http_build_query([
                    "lang" => $_POST['from_laguage'].'-'.$_POST['to_language'],
                    "text"  => $_POST['text'],
                ]);
                
                $sData = fnHTTPRequest("https://translate.yandex.net/api/v1/tr.json/translate?srv=yawidget&".$aParameters);
                $aData = json_decode($sData, true);
                
                if (!isset($aData['text'][0])) {
                    throw new Exception("Can't be translated");
                }
                    
                $aResponse['data'] = $aData['text'][0];
            } else {
                throw new Exception("Wrong provider");
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
    
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/x-icon;base64,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">
</head>

<body>
    <div id="application"></div>
    <script src="dist/main.js" type="text/javascript"></script>
</body>

</html>
