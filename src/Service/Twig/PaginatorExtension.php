<?php

namespace App\Service\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\HttpFoundation\Request;



class PaginatorExtension extends AbstractExtension

{
    protected $container;


    /**
     * Set services
     *
     * @DI\InjectParams({
     *     "container"             = @DI\Inject("service_container"),
     * })
     *
     * @param UserManager     $userManager
     * @param FileManager     $fileManager
     * @param LicenceManager $licenceManager
     * @param TokenGenerator        $tokenGenerator
     */
    public function setServices(
        ContainerInterface $container
    ) {
        $this->container             = $container;
    }

    public function __construct(ContainerInterface $container)
    {
        $this->setServices($container);
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('paginator', array($this, 'main'), ['is_safe' => ['html']])
        );
    }



    protected  function pager ($request, $i, $self) {
        if ($request->get('search_by') != null) {
            $urlPaginate =  $self->router->generate($this->route_name , [
                'page'      => $i,
                'search_by' => $request->get('search_by') 
            ]);
        } else {
            $urlPaginate =  $self->router->generate($this->route_name  , [
                'page'      => $i,
            ]);
        }

        return $urlPaginate;
    }


    protected function pagerList( $self,$currpage, $request, $i, $content = null) {
        if ($content  == null) {
            $content = $i;
        }

        $html ='';
        $html .= '<li class="page-item page-'.$i.' '.($currpage == $i ? 'active' : '').' " aria-disabled="false" aria-selected="'.($currpage==$i ? true : '').'">';
        $urlPaginate = $self->pager ($request, $i, $this);  
        $html .='<a data-page="'.$i.'" class="page-link"  href="'.$urlPaginate.'">'.$content.'</a>';
        $html .= '</li>';
        return $html;
    }

    public function main(array $paginate, $route_name, $id='app_paginator') {

        $request = Request::createFromGlobals();
        $this->route_name = $route_name;

        $this->router = $this->container->get('router');

        $html = "";

        if ($paginate["nbpages"]!=0) {
            $nbpages = $paginate["nbpages"];
            $html .= '<nav aria-label="Page navigation example" class="bootgrid-footer container-fluid">';
				$html .= '<div class="row">';
                    $html .= '<div class="col-md-12" style="padding-left: 0px;" id="'.$id.'">';
                        $html .= '<ul class="pagination">';
                            $currpage = $paginate["currentPage"];
     
                            $html .='<li class="page-item first '.($currpage == 1 ? "disabled" : '').' aria-label-disabled=" '.($currpage == 1 ? true : ($currpage != 1 ? "disabled" : false)).'" >';
                                $html .= '<a data-page="first" class="page-link button" href="'. $this->router->generate($route_name,["page" => 1]) .'">«</a>';
                            $html .= '</li>';

                            $html .='<li class="page-item prev '. ($currpage == 1 ? "disabled" : '').' aria-disabled=" '.($currpage == 1 ? "true" : ($currpage != 1 ? "false" : "")).' ">';
                            $html .='<a data-page="prev" class="page-link button" 
                                    '.($currpage != 1 ? 'href="'.$this->router->generate($route_name, ["page" => $currpage-1]).'"' : '').'
                                >&lt;</a>';
                            
                            $html .='</li>';

                                $first_limit = 5;
                                $second_limit = $nbpages -1;
                                $first_distance = 2;

                            $initialization = 1;
                            
                            $middle = ceil($nbpages / 2) ;
                            $old_pages = [];

                            if ($currpage > $first_limit) {
                                if ($nbpages == $currpage) { // courrant est se situe au dernier element
                                    $initialization = $currpage -3;
                                } else {
                                    $initialization = $currpage -2;
                                }
                            }

                            
                            if ($initialization != 1) {
                                $html .= $this->pagerList($this,  $currpage, $request, 1,1);
                                $html .= $this->pagerList($this,  $currpage, $request, '','...');
                                
                                if ( ($currpage + 4) > $middle) {

                                    $middle_limit = ($middle+ 2);

                                    if ($currpage > $middle 
                                    && $currpage > $middle + 1
                                    && $currpage > $middle + 2
                                    && $currpage > $middle + 3) {
                                        for ($i = $middle; $i< $middle_limit; $i++) {
                                            if (!in_array($i, $old_pages)) {
                                                array_push($old_pages, $i);
                                                $html .= $this->pagerList($this,  $currpage, $request,  $i, $i. '');
                                            } 
                                        }
                                        
                                        if ($currpage -1 != $middle_limit) {
                                            if ($middle_limit != ($currpage -2)) {

                                                $html .= $this->pagerList($this,  $currpage, $request, '','...');                                   
                                            }
                                        }
                                    }
                                }
                            }

                            $i = $initialization ;


                            for ($i; $i <= $nbpages; $i++) {

                                if ($i < $first_limit) {
                                    array_push($old_pages, $i);
                                    $html .= $this->pagerList($this,  $currpage, $request,  $i, $i. '');
                                }else
                                if ($i < ($currpage + $first_distance) ) {
                                    if (!in_array($i, $old_pages)) {
                                        array_push($old_pages, $i);
                                        $html .= $this->pagerList($this,  $currpage, $request,  $i, $i.'');
                                    } else {continue;}
                                }
                                 
                            }
                           
                            // if ($currpage + 1 == $paginate["nbpages"])
                            for ($i = 1; $i < $nbpages; $i++) { 
                                
                                if ($currpage < $second_limit) {

                                    if ($i == ($first_limit + $first_distance) ) {
                                        $html .= $this->pagerList($this,  $currpage, $request, '','...');
                                    }
                                }
                            }
                            $endPage = false;
                            // la si la page courrant est toujour ajouter de 1 visible deriere lui
                            for ($i = 1; $i <= $nbpages; $i++) {
                                if (
                                    $i >= $second_limit 
                                    && ($i) == $nbpages
                                    && ($currpage +1) < $nbpages
                                ) {
                                    if (!in_array($i, $old_pages)) {
                                        array_push($old_pages, $i);
                                        $html .= $this->pagerList($this,  $currpage, $request,  $i, $i.'');
                                    } else {continue;}
                                    $endPage = $i;
                                }
                                if ($currpage < $second_limit) {
                                    if ($i >= $second_limit && ($currpage +1)< $second_limit) {
                                        if ($endPage == $i) {continue;}
                                        
                                        if (!in_array($i, $old_pages)) {
                                            array_push($old_pages, $i);
                                            $html .= $this->pagerList($this,  $currpage, $request,  $i, $i.'');
                                        } else {continue;}
                                    } 
                                }
                                 else if (
                                    $currpage != $nbpages
                                    && ($currpage +1 != $nbpages) 
                                    && $nbpages == $i) {
                                    
                                        if (!in_array($i, $old_pages)) {
                                            array_push($old_pages, $i);
                                            $html .= $this->pagerList($this,  $currpage, $request,  $i, $i.'');
                                        } else {continue;}
                                }
                            }
                            
                           $html .='<li class="page-item next '.($currpage == $nbpages ? "disabled" : "").' " aria-disabled="'.($currpage == $nbpages ? true : "").'">';
                                   $html .= '<a data-page="next" class="page-link button" href="'.$this->router->generate($route_name, ["page" => $currpage+1]).'">&gt;</a>';
                           $html .= '</li>';
						
                            $html .= '<li class="page-item last '.($currpage== $nbpages ? "disabled" : '').'" aria-disabled=" '.($currpage== $nbpages ? "true" : "").' ">';
                               $html .= '<a data-page="last" class="page-link button" href="'.$this->router->generate($route_name, [
                                "page" => $nbpages
                               ]).'">»</a>';

                            $html .= '</li>';
                        $html .='</ul>';
                    $html .= '</div>';
                $html .='</div>';
            $html .='</nav>';
        }

        return $html;
    }

}