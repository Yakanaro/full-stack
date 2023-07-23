<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [
            'id' => 0,
            'plot_id' => 0,
            'access' => 0,
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => '',
            'plots' => '',
        ];
        // info
        $q = DB::query("SELECT user_id, phone, access, first_name, last_name, email, plot_id FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'plot_id' => (int) $row['plot_id'],
                'access' => (int) $row['access'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'plots' => $row['plot_id'],
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }
    public static function users_list($d = [])
    {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            $where[] = "first_name LIKE '%" . $search . "%'";
            $where[] = "phone LIKE '%" . $search . "%'";
            $where[] = "email LIKE '%" . $search . "%'";
        }
        $where = $where ? "WHERE " . implode(" OR ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email, last_login
            FROM users " . $where . " LIMIT " . $offset . ", " . $limit . ";") or die (DB::error());

        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int)$row['user_id'],
                'plot_id' => $row['plot_id'] ? $row['plot_id'] : '-',
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'last_login' => $row['last_login'] ? date('Y/m/d', $row['last_login']): '-'
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users " . $where . ";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users';
        if ($search) $url .= '?search=' . $search . '&';
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = [])
    {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }
    public static function user_edit_delete($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        if (!$user_id) return;
        //info
        $q = DB::query("DELETE FROM users WHERE user_id =".$user_id.";");

        return User::users_fetch(['offset' => $offset]);
    }

    public static function user_edit_window($d = []) {
        HTML::assign('user', User::user_info($d));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
        //vars
        $user_id = $d['user_id'] ?? 0;
        $first_name = trim($d['first_name'] ?? '');
        $last_name = trim($d['last_name'] ?? '');
        $phone = preg_replace('~\D+~', '', $d['phone'] ?? '');
        $email = strtolower(trim($d['email'] ?? ''));
        $plots = trim($d['plots'] ?? '');
        $offset = preg_replace('~\D+~', '', $d['offset'] ?? '');
        //update
        if ($user_id) {
            $set = [];
            $set[] = "first_name='$first_name'";
            $set[] = "last_name='$last_name'";
            $set[] = "phone='$phone'";
            $set[] = "email='$email'";
            $set[] = "plot_id='$plots'";
            $set[] = "updated='".Session::$ts."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET $set WHERE user_id='$user_id' LIMIT 1;") or die (DB::error());
        } else {
            $values = compact('first_name', 'last_name', 'phone', 'email', 'plots');
            $values = "'" . implode("','", $values) . "'";

            DB::query("INSERT INTO users (
            first_name,
            last_name,
            phone,
            email,
            plot_id
        ) VALUES ($values);") or die (DB::error());
        }
        //output
        return User::users_fetch(['offset' => $offset]);
    }
}
