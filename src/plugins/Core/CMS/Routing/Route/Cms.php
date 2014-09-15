<?php

namespace Core\Routing\Route;

class Cms extends Route
{
  /**
   * 
   * @var CmsPage
   */
  private $page;
  
  public function matchesUrl($url)
  {
    
    
    $urlParts = parse_url($url);
    
    $extension = $this->getExtension($urlParts['path']);
    $path = $urlParts['path'];  // "/chi-siamo.html"
    
    $conn = Core::getCurrentInstance()->getDataController()->getCurrentConnection();
    $this->page = CmsPageTable::getInstance()
      ->createQuery('p')
      ->innerJoin('p.Translation t WITH ( t.lang = ? AND t.slug = ? )', array('it', $path))
      ->andWhere('p.site = ?', array(Core::getCurrentInstance()->getSite()))
      ->limit(1)
      ->execute()
      ->getFirst();

    if($this->page)
    {
      return true;
    }
    return false;
  }
  
  public function getPage()
  {
    return $this->page;
  }
  
}
