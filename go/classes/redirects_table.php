<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class local_go_redirects_table extends table_sql {
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        
        $columns = [
            'select',  // 0.
            'shortname',
            'url',
            'status',
            'category',
            'comment',
            'timemodified',
            'actions'  // 7.
        ];
        
        $headers = [
            '<input type="checkbox" id="select-all" />',
            get_string('shortname', 'local_go'),
            get_string('targeturl', 'local_go'),
            get_string('status', 'local_go'),
            get_string('category', 'local_go'),
            get_string('comment', 'local_go'),
            get_string('lastmodified', 'local_go'),
            get_string('actions', 'local_go')
        ];
        
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->no_sorting('select');
        $this->no_sorting('actions');
        $this->column_class(0 /* 'select' */, 'text-center');
        $this->column_class(7 /* 'actions' */, 'text-center');
        
        $this->set_sql('*', '{local_go}', '1=1');
    }
    
    protected function col_select($row) {
        return '<input type="checkbox" name="ids[]" value="'.$row->id.'" />';
    }
    
    protected function col_shortname($row) {
        $url = local_go_make_url_from_shortname($row->shortname);
        return '<a href="'.$url.'" target=_blank>'.$row->shortname.'</a>';
    }
    
    protected function col_url($row) {
        return '<a href="'.$row->url.'" target=_blank>'.$row->url.'</a>';
    }
    
    protected function col_status($row) {
        return $row->status ? get_string('enabled', 'local_go') : get_string('disabled', 'local_go');
    }
    
    protected function col_timemodified($row) {
        return '<small>'.userdate($row->timemodified).'</small>';
    }
    
    protected function col_actions($row) {
        global $OUTPUT, $PAGE;
        
        $actions = [];
        $baseurl = $PAGE->url;
        
        // Edit.
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($baseurl, ['action' => 'edit', 'id' => $row->id]),
            new pix_icon('t/edit', get_string('edit'))
        );
        
        // Toggle status.
        $icon = $row->status ? 't/hide' : 't/show';
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($baseurl, ['action' => 'toggle', 'id' => $row->id, 'sesskey' => sesskey()]),
            new pix_icon($icon, get_string($row->status ? 'disable' : 'enable'))
        );
        
        // Clone.
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($baseurl, ['action' => 'clone', 'id' => $row->id, 'sesskey' => sesskey()]),
            new pix_icon('t/copy', get_string('clone'))
        );
        
        // Delete.
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($baseurl, ['action' => 'delete', 'id' => $row->id, 'sesskey' => sesskey()]),
            new pix_icon('t/delete', get_string('delete'))
        );
        
        return implode(' ', $actions);
    }
    
    public function wrap_html_start() {
        echo '<form action="'.$this->baseurl.'" method="post" id="redirects-form">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    }
    
    public function wrap_html_finish() {
        echo '<div class="form-actions">';
        echo '<select name="bulkaction" class="form-control">';
        echo '<option value="">'.get_string('withselected', 'local_go').'</option>';
        echo '<option value="enable">'.get_string('enable').'</option>';
        echo '<option value="disable">'.get_string('disable').'</option>';
        echo '<option value="delete">'.get_string('delete').'</option>';
        echo '</select>';
        echo '<button type="submit" class="btn btn-secondary ml-2">'.get_string('go').'</button>';
        echo '</div>';
        echo '</form>';
        
        // Select all checkboxes.
        echo '<script>
            document.getElementById("select-all").addEventListener("change", function() {
                var checkboxes = document.querySelectorAll(\'input[name="ids[]"]\');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = this.checked;
                }, this);
            });
        </script>';
    }
}
