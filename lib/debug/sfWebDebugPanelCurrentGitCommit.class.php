<?php

/**
 * @desc : Show current git commit in sf web debug toolbar
 * @author Charles Vallantin Dulac <charles.vallantin-dulac@sporteasy.fr>
 *
 * Put this code in config/projectConfiguration to active module:
 *     //Git debug tool bar
 *     $this->dispatcher->connect('debug.web.load_panels', array(
 *        'acWebDebugPanelCurrentGitCommit',
 *        'listenToLoadDebugWebPanelEvent'
 *     ));
 *
 */
class acWebDebugPanelCurrentGitCommit extends sfWebDebugPanel
{
  public function __construct(sfWebDebug $webDebug)
  {
    parent::__construct($webDebug);
    $this->getGitInformation();
  }


  protected function runCommand($command)
  {
    $descriptorspec = array(
      1 => array('pipe', 'w'),
      2 => array('pipe', 'w'),
    );
    $pipes = array();
    $resource = proc_open($command, $descriptorspec, $pipes, $this->repo);

    $value = stream_get_contents($pipes[1]);
    //$stderr = stream_get_contents($pipes[2]);
    foreach ($pipes as $pipe) {
      fclose($pipe);
    }

    //$status = trim(proc_close($resource));
    //if ($status) throw new Exception($stderr);

    return $value;
  }


  protected function getGitInformation()
  {
    $this->commit = '-';
    $this->commit_details = '';

    //Check if sf projet root path is a git repo
    $this->repo = dirname(__FILE__)."/../..";
    if (!is_dir($this->repo.'/.git'))
    {
      return false;
    }

    //Check if git is installed on computeur
    $git = str_replace("\n", '', $this->runCommand('which git'));
    if ($git == '')
    {
      return false;
    }

    //Get current commit (sha1)
    $this->commit =  str_replace("\n", '',
              $this->runCommand($git.' log --pretty=tformat:"%h" -1'));

    //Get current commit details
    $this->commit_details = str_replace(array('(',')',"\n"), '',
              $this->runCommand($git.' log --pretty=tformat:"%d" -1'));

    //Check if there is a tag:
    if ($this->commit_details != "(HEAD)")
    {
      $names = explode(', ', $this->commit_details);

      //get all tags
      $tags = explode("\n", $this->runCommand($git.' tag -l'));

      $this->commit .= ', ';

      $size = count($names);
      for($i = 0; $i < $size ; $i++)
      {
        if ($names[$i] != '-')
        {
          if (in_array($names[$i], $tags))
          {
            $this->commit .= str_replace(' ', '',$names[$i]).', ';
          }
        }
      }
      $this->commit = substr($this->commit,0,-2);
    }

    return true;
  }


  public function getTitle()
  {
    return '<img src="/img/git.png" alt="Git current commit" height="16" width="16" /> '.$this->commit;
  }


  public function getPanelTitle()
  {
    return $this->commit_details;
  }

  public function getPanelContent()
  {

    return $this->commit_details;
  }


  public static function listenToLoadDebugWebPanelEvent(sfEvent $event)
  {
    $event->getSubject()->setPanel(
      'currentGitCommit',
      new self($event->getSubject())
    );
  }
}
?>
