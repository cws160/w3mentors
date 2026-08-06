<?php

class Action
{
    /* Types */

    const VIEW = 1;
    const EDIT = 2;
    const REMOVE = 3;
    const OTHER = 4;

    private $recordId;
    private $buttons = [];

    public function __construct($recordId)
    {
        $this->recordId = $recordId;
    }

    public  function addViewBtn(string $title, string $onclick, array $attributes = []): void
    {
        $this->addBtn(self::VIEW, $title, $onclick, 'view', 'javascript:void(0);', null, $attributes);
    }

    public  function addEditBtn(string $title, string $onclick, array $attributes = []): void
    {
        $this->addBtn(self::EDIT, $title, $onclick, 'edit', 'javascript:void(0);', null, $attributes);
    }

    public  function addRemoveBtn(string $title, string $onclick, array $attributes = []): void
    {
        $this->addBtn(self::REMOVE, $title, $onclick, 'delete', 'javascript:void(0);', null, $attributes);
    }

    public  function addCancelBtn(string $title, string $onclick, array $attributes = []): void
    {
        $this->addBtn(self::REMOVE, $title, $onclick, 'close', 'javascript:void(0);', null, $attributes);
    }

    public  function addOtherBtn(string $title, string $onclick, string $icon, string $href = 'javascript:void(0);', string $target = null, array $attributes = []): void
    {
        $this->addBtn(self::OTHER, $title, $onclick, $icon, $href, $target, $attributes);
    }

    private  function addBtn(string $type, string $title, string $onclick, string $icon, string $href, string $target = null, array $attributes = []): void
    {
        array_push($this->buttons, ['type' => $type, 'title' => $title, 'onclick' => $onclick, 'icon' => $icon, 'href' => $href, 'target' => $target, 'attributes' => $attributes]);
    }

    public  function renderHtml(): string
    {
        $counter = 0;
        $ul = $parentUl = new HtmlElement("ul", array("class" => "actions"));
        foreach ($this->buttons as $button) {
            $counter++;
            if (count($this->buttons) > 3 && $counter == 3) {
                $li = $parentUl->appendElement('li', ['class' => 'dropdown dropdown-static', 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => Label::getLabel('LBL_ACTIONS')]);
                $li->appendElement('a', array('href' => 'javascript:void(0)', 'data-bs-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false'), '<svg class="svg"><use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite-actions.svg#more-dots"></use></svg>', true);
                $li = $li->appendElement('div', array('class' => 'dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim'));
            }
            $svg = '<svg width="18" height="18" class="svg"><use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite-actions.svg#' . $button['icon'] . '"></use></svg>';
            $attr = ['href' => $button['href'], "onclick" => $button['onclick']];
            $attr = array_merge($attr, $button['attributes']);
            if (!is_null($button['target'])) {
                $attr['target'] = $button['target'];
            }
            if ($counter >= 3 && count($this->buttons) > 3) {
                $svg = '<i class="icn">' . $svg . '</i>' . $button['title'];
                $attr['class'] = 'dropdown-item';
                $attr = array_merge($attr, ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
            } else {
                $li = $ul->appendElement('li', ['title' => $button['title'], 'data-bs-toggle' => 'tooltip']);
            }

            $li->appendElement('a', $attr, $svg, true);
        }
        return $parentUl->getHtml();
    }
}
