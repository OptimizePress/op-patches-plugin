<?php

class Op_Patches_Skin extends WP_Upgrader_Skin
{
    protected $slug;

    public function set_slug($slug)
    {
        $this->slug = $slug;
    }

    public function add_strings()
    {
        $this->upgrader->strings['downloading_package']   = __('Downloading patch (%s)...', $this->slug);
        $this->upgrader->strings['download_failed']       = __('Downloading patch (%s)...', $this->slug);
        $this->upgrader->strings['unpack_package']        = __('Unpacking...', $this->slug);
        $this->upgrader->strings['installing_package']    = __('Installing patch...', $this->slug);
    }

    public function success($message)
    {
        echo '<div class="updated"><p>' . $message . '</p></div>';

        $this->reset();
        $this->flush_output();
    }

    public function reset() {
        $this->in_loop = false;
        $this->error = false;
    }

    public function flush_output() {
        wp_ob_end_flush_all();
        flush();
    }
}