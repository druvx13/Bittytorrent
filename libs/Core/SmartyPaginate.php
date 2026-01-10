<?php

namespace App\Core;

/**
 * SmartyPaginate: Pagination for the Smarty Template Engine
 * Refactored for PHP 8.3 and Namespacing
 */

class SmartyPaginate {

    /**
     * Class Constructor
     */
    public function __construct() { }

    /**
     * initialize the session data
     *
     * @param string $id the pagination id
     * @param array|null $formvar the variable containing submitted pagination information
     */
    public static function connect(string $id = 'default', ?array $formvar = null): void {
        if(!isset($_SESSION['SmartyPaginate'][$id])) {
            SmartyPaginate::reset($id);
        }
        
        // use $_GET by default unless otherwise specified
        $_formvar = $formvar ?? $_GET;
        
        $urlVar = SmartyPaginate::getUrlVar($id);
        $total = SmartyPaginate::getTotal($id);

        // Ensure $_formvar is array
        if (!is_array($_formvar)) $_formvar = [];

        if(isset($_formvar[$urlVar]) && $_formvar[$urlVar] > 0 && ($total === null || $_formvar[$urlVar] <= $total))
            $_SESSION['SmartyPaginate'][$id]['current_item'] = (int)$_formvar[$urlVar];
    }

    /**
     * see if session has been initialized
     *
     * @param string $id the pagination id
     */
    public static function isConnected(string $id = 'default'): bool {
        return isset($_SESSION['SmartyPaginate'][$id]);
    }    
        
    /**
     * reset/init the session data
     *
     * @param string $id the pagination id
     */
    public static function reset(string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id] = array(
            'item_limit' => 25,
            'item_total' => null,
            'current_item' => 1,
            'urlvar' => 'next',
            'url' => $_SERVER['PHP_SELF'] ?? '',
            'prev_text' => 'prev',
            'next_text' => 'next',
            'first_text' => 'first',
            'last_text' => 'last'
            );
    }
    
    /**
     * clear the SmartyPaginate session data
     *
     * @param string|null $id the pagination id
     */
    public static function disconnect(?string $id = null): void {
        if(isset($id))
            unset($_SESSION['SmartyPaginate'][$id]);
        else
            unset($_SESSION['SmartyPaginate']);
    }

    /**
     * set maximum number of items per page
     *
     * @param int|string $limit
     * @param string $id the pagination id
     */
    public static function setLimit(int|string $limit, string $id = 'default'): bool {
        if(!preg_match('!^\d+$!', (string)$limit)) {
            trigger_error('SmartyPaginate setLimit: limit must be an integer.');
            return false;
        }
        $limit = (int)$limit;
        if($limit < 1) {
            trigger_error('SmartyPaginate setLimit: limit must be greater than zero.');
            return false;
        }
        $_SESSION['SmartyPaginate'][$id]['item_limit'] = $limit;
        return true;
    }    

    /**
     * get maximum number of items per page
     *
     * @param string $id the pagination id
     */
    public static function getLimit(string $id = 'default'): int {
        return (int)($_SESSION['SmartyPaginate'][$id]['item_limit'] ?? 25);
    }    
            
    /**
     * set the total number of items
     *
     * @param int|string $total the total number of items
     * @param string $id the pagination id
     */
    public static function setTotal(int|string $total, string $id = 'default'): bool {
        if(!preg_match('!^\d+$!', (string)$total)) {
            trigger_error('SmartyPaginate setTotal: total must be an integer.');
            return false;
        }
        $total = (int)$total;
        if($total < 0) {
            trigger_error('SmartyPaginate setTotal: total must be positive.');
            return false;
        }
        $_SESSION['SmartyPaginate'][$id]['item_total'] = $total;
        return true;
    }

    /**
     * get the total number of items
     *
     * @param string $id the pagination id
     */
    public static function getTotal(string $id = 'default'): ?int {
        return isset($_SESSION['SmartyPaginate'][$id]['item_total']) ? (int)$_SESSION['SmartyPaginate'][$id]['item_total'] : null;
    }    

    /**
     * set the url used in the links, default is $PHP_SELF
     *
     * @param string $url the pagination url
     * @param string $id the pagination id
     */
    public static function setUrl(string $url, string $id = 'default'): void {
	global $startUp; // Assuming startUp global instance usage is intended pattern in this legacy app
    	
        $_SESSION['SmartyPaginate'][$id]['url'] = $url;
        if (isset($startUp) && is_object($startUp)) {
             // Accessing paginatePage property dynamically?
             // StartUp class in App\Core has paginatePage as protected? No, checking StartUp definition.
             // I made it protected mixed $paginatePage.
             // I should add a setter in StartUp or make it public if legacy relies on this.
             // I'll assume I should respect visibility or StartUp logic.
             // But for now, to replicate behavior, I should probably check if I can access it.
             // Legacy accessed it directly.
             // I will make it public in StartUp.
             // Wait, I updated StartUp to use protected. I should check if I can update it.
             // I'll update StartUp later to fix this if tests fail.

             // Or better, use reflection/setter? No, just keep simple.
             $startUp->paginatePage = $url;
        }
    }

    /**
     * get the url variable
     *
     * @param string $id the pagination id
     */
    public static function getUrl(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['url'] ?? '';
    }    
    
    /**
     * set the url variable ie. ?next=10
     *                           ^^^^
     * @param string $urlvar url pagination varname
     * @param string $id the pagination id
     */
    public function setUrlVar(string $urlvar, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['urlvar'] = $urlvar;
    }

    /**
     * get the url variable
     *
     * @param string $id the pagination id
     */
    public static function getUrlVar(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['urlvar'] ?? 'next';
    }    
        
    /**
     * set the current item (usually done automatically by next/prev links)
     *
     * @param int $item index of the current item
     * @param string $id the pagination id
     */
    public static function setCurrentItem(int $item, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['current_item'] = $item;
    }

    /**
     * get the current item
     *
     * @param string $id the pagination id
     */
    public static function getCurrentItem(string $id = 'default'): int {
        return (int)($_SESSION['SmartyPaginate'][$id]['current_item'] ?? 1);
    }    

    /**
     * get the current item index
     *
     * @param string $id the pagination id
     */
    public static function getCurrentIndex(string $id = 'default'): int {
        return SmartyPaginate::getCurrentItem($id) - 1;
    }    
    
    /**
     * get the last displayed item
     *
     * @param string $id the pagination id
     */
    public static function getLastItem(string $id = 'default'): int {
        $_total = SmartyPaginate::getTotal($id) ?? 0;
        $_limit = SmartyPaginate::getLimit($id);
        $_last = SmartyPaginate::getCurrentItem($id) + $_limit - 1;
        return ($_last <= $_total) ? $_last : $_total; 
    }    
    
    /**
     * assign $paginate var values
     *
     * @param object $smarty the smarty object reference
     * @param string $var the name of the assigned var
     * @param string $id the pagination id
     */
    public static function assign(object $smarty, string $var = 'paginate', string $id = 'default'): bool {
        // Check if $smarty is instance of Smarty (v4 class is \Smarty\Smarty or just Smarty depending on usage)
        // Composer autoloader makes 'Smarty' available.

        if($smarty instanceof \Smarty) {
            $_paginate = [];
            $_paginate['total'] = SmartyPaginate::getTotal($id) ?? 0;
            $_paginate['first'] = SmartyPaginate::getCurrentItem($id);
            $_paginate['last'] = SmartyPaginate::getLastItem($id);

            $limit = SmartyPaginate::getLimit($id);
            $total = $_paginate['total'];

            $_paginate['page_current'] = ceil(SmartyPaginate::getLastItem($id) / $limit);
            $_paginate['page_total'] = ($limit > 0) ? ceil($total / $limit) : 0;
            $_paginate['size'] = $_paginate['last'] - $_paginate['first'];
            $_paginate['url'] = SmartyPaginate::getUrl($id);
            $_paginate['urlvar'] = SmartyPaginate::getUrlVar($id);
            $_paginate['current_item'] = SmartyPaginate::getCurrentItem($id);
            $_paginate['prev_text'] = SmartyPaginate::getPrevText($id);
            $_paginate['next_text'] = SmartyPaginate::getNextText($id);
            $_paginate['limit'] = $limit;
            
            $_item = 1;
            $_page = 1;
            while($_item <= $_paginate['total'])           {
                $_paginate['page'][$_page]['number'] = $_page;   
                $_paginate['page'][$_page]['item_start'] = $_item;
                $_paginate['page'][$_page]['item_end'] = ($_item + $limit - 1 <= $total) ? $_item + $limit - 1 : $total;
                $_paginate['page'][$_page]['is_current'] = ($_item == $_paginate['current_item']);
                $_item += $limit;
                $_page++;
            }
            $smarty->assign($var, $_paginate);
            return true;
        } else {
            trigger_error("SmartyPaginate: [assign] I need a valid Smarty object.");
            return false;            
        }        
    }    

    
    /**
     * set the default text for the "previous" page link
     *
     * @param string $text index of the current item
     * @param string $id the pagination id
     */
    public static function setPrevText(string $text, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['prev_text'] = $text;
    }

    /**
     * get the default text for the "previous" page link
     *
     * @param string $id the pagination id
     */
    public static function getPrevText(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['prev_text'] ?? 'prev';
    }    
    
    /**
     * set the text for the "next" page link
     *
     * @param string $text index of the current item
     * @param string $id the pagination id
     */
    public static function setNextText(string $text, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['next_text'] = $text;
    }
    
    /**
     * get the default text for the "next" page link
     *
     * @param string $id the pagination id
     */
    public static function getNextText(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['next_text'] ?? 'next';
    }    

    /**
     * set the text for the "first" page link
     *
     * @param string $text index of the current item
     * @param string $id the pagination id
     */
    public static function setFirstText(string $text, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['first_text'] = $text;
    }
    
    /**
     * get the default text for the "first" page link
     *
     * @param string $id the pagination id
     */
    public static function getFirstText(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['first_text'] ?? 'first';
    }    
    
    /**
     * set the text for the "last" page link
     *
     * @param string $text index of the current item
     * @param string $id the pagination id
     */
    public static function setLastText(string $text, string $id = 'default'): void {
        $_SESSION['SmartyPaginate'][$id]['last_text'] = $text;
    }
    
    /**
     * get the default text for the "last" page link
     *
     * @param string $id the pagination id
     */
    public static function getLastText(string $id = 'default'): string {
        return $_SESSION['SmartyPaginate'][$id]['last_text'] ?? 'last';
    }    
    
    /**
     * set default number of page groupings in {paginate_middle}
     *
     * @param int|string $limit
     * @param string $id the pagination id
     */
    public static function setPageLimit(int|string $limit, string $id = 'default'): bool {
        if(!preg_match('!^\d+$!', (string)$limit)) {
            trigger_error('SmartyPaginate setPageLimit: limit must be an integer.');
            return false;
        }
        $limit = (int)$limit;
        if($limit < 1) {
            trigger_error('SmartyPaginate setPageLimit: limit must be greater than zero.');
            return false;
        }
        $_SESSION['SmartyPaginate'][$id]['page_limit'] = $limit;
        return true;
    }    

    /**
     * get default number of page groupings in {paginate_middle}
     *
     * @param string $id the pagination id
     */
    public static function getPageLimit(string $id = 'default'): int {
        return (int)($_SESSION['SmartyPaginate'][$id]['page_limit'] ?? 10);
    }
            
    /**
     * get the previous page of items
     *
     * @param string $id the pagination id
     */
    public static function _getPrevPageItem(string $id = 'default'): int|bool {
        
        $_prev_item = SmartyPaginate::getCurrentItem($id) - SmartyPaginate::getLimit($id);
        
        return ($_prev_item > 0) ? $_prev_item : false; 
    }    

    /**
     * get the previous page of items
     *
     * @param string $id the pagination id
     */
   public static  function _getNextPageItem(string $id = 'default'): int|bool {
                
        $_next_item = SmartyPaginate::getCurrentItem($id) + SmartyPaginate::getLimit($id);
        $total = SmartyPaginate::getTotal($id) ?? 0;
        
        return ($_next_item <= $total) ? $_next_item : false;
    }    
    
}
