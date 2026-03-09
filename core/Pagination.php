<?php

namespace PHPFramework;

class Pagination
{
        public int $count_pages = 1;
        public int $current_page = 1;
        public string $uri = '';
        public int $mid_size = 3;
        public int $max_pages = 7;


        public function __construct(public int $page = 1, public int $per_page = 1, public int $total = 1)
        {
            $this->count_pages = $this->getCountPages();
            $this->current_page = $this->getCurrentPage();
            $this->uri = $this->getParams();
            $this->mid_size = $this->getMidSize();
        }

        protected function getCountPages(): int
        {
            return (int)ceil($this->total / $this->per_page) ?: 1;

        }

        protected function getCurrentPage(): int
        {
            if (($this->page < 1) || ($this->page > $this->count_pages)){
                abort();
            }
            return $this->page;
        }

        protected function getParams()
        {
            $url = $_SERVER['REQUEST_URI'];
            $url = explode('?',$url);
            $uri = $url[0];
            if(isset($url[1]) && !in_array($url[1],['','&'])){
              $uri .= '?';
              $params = explode('&',$url[1]);
              foreach ($params as $param){
                  if (!str_contains($param,'page=')){
                      $uri .= "{$param}&";
                  }
              }
            }
            return $uri;

        }


        protected function getMidSize(): int
        {
            return ($this->count_pages <=$this->max_pages) ? $this->count_pages : $this->mid_size;
        }

        public function getStart(): string
        {
            return ($this->current_page - 1) * $this->per_page;
        }

    public function getHtml(): string
    {
        $prev_link = '';
        $next_link = '';
        $pages_html = '';

        // Генерация ссылок "Предыдущая страница" и "Следующая страница"
        if ($this->current_page > 1) {
            $prev_link = "<a href='" . $this->getLink($this->current_page - 1) . "' class='prev'>Previous</a>";
        }

        if ($this->current_page < $this->count_pages) {
            $next_link = "<a href='" . $this->getLink($this->current_page + 1) . "' class='next'>Next</a>";
        }

        // Генерируем ссылки на страницы вокруг текущей страницы
        for ($i = max(1, $this->current_page - $this->mid_size); $i <= min($this->count_pages, $this->current_page + $this->mid_size); $i++) {
            if ($i === $this->current_page) {
                $pages_html .= "<a href='#' class='active'>{$i}</a>";
            } else {
                $pages_html .= "<a href='" . $this->getLink($i) . "'>{$i}</a>";
            }
        }

        return '
            <div class="text-start py-4">
                <div class="custom-pagination">
                    ' . $prev_link . '
                    ' . $pages_html . '
                    ' . $next_link . '
                </div>
            </div>';
    }

    protected function getLink($page):string
        {
            if ($page == 1)
            {
                return rtrim($this->uri, '?&');
            }

            if (str_contains($this->uri, '&') || str_contains($this->uri, '?')){
                return "{$this->uri}page={$page}";
            }else{
                return "{$this->uri}?page={$page}";
            }

        }

        public function __toString(): string
        {
            return $this->getHtml();
        }

}