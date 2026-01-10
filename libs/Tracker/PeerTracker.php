<?php

namespace App\Tracker;

use App\Database\DB;

// Tracker Error Exception or Function
if (!function_exists('App\Tracker\tracker_error')) {
    function tracker_error($error)
    {
	    exit('d14:failure reason' . strlen($error) . ":{$error}e");
    }
}

class PeerTracker
{
    // We will use the existing DB instance instead of creating a new connection logic
    // This simplifies configuration handling and connection pooling

    public static function open()
    {
        // No-op as DB is singleton handled by App\Database\DB
        // But we might want to check connection
        try {
            DB::getInstance();
        } catch (\Exception $e) {
            tracker_error('Database connection failed');
        }
    }

    public static function close()
    {
        // No-op
    }

    public static function clean()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'] ?? [];
        $clean_idle_peers = $tracker_conf['clean_idle_peers'] ?? 1;
        $announce_interval = $tracker_conf['announce_interval'] ?? 1800;
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        if (mt_rand(1, $clean_idle_peers) == 1)
        {
            $time = time();

            // fetch last cleanup time
            $last = $db->get_var(
                "SELECT value FROM `{$db_prefix}tasks` WHERE name='prune'"
            );

            // first clean cycle?
            if (!$last)
            {
                $db->query(
                    "REPLACE INTO `{$db_prefix}tasks` VALUES('prune', {$time})"
                );

                $db->query(
                    "DELETE FROM `{$db_prefix}peers` WHERE updated < " .
                    ($time - ($announce_interval * 2))
                );
            }
            // prune idle peers
            elseif (($last + $announce_interval) < $time)
            {
                $db->query(
                    "UPDATE `{$db_prefix}tasks` SET value={$time} WHERE name='prune'"
                );

                $db->query(
                    "DELETE FROM `{$db_prefix}peers` WHERE updated < " .
                    ($time - ($announce_interval * 2))
                );
            }
        }
    }

    public static function new_peer()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $peer_id = $db->escape($_GET['peer_id']);
        $ip = $db->escape($_GET['ip']);
        $port = (int)$_GET['port'];
        $seeding = (int)$tracker_conf['seeding'];

        // compact: 6-byte compacted peer info
        // pack('Nn', ip2long($_GET['ip']), $_GET['port'])
        // We need to escape binary data if we use it in SQL string directly.
        // PDO might handle it if we used params, but we are using our wrapper.
        // Our wrapper uses quote().
        // Legacy used mysql_real_escape_string.
        $compact = $db->escape(pack('Nn', ip2long($_GET['ip']), $port));

        $db->query(
            "INSERT IGNORE INTO `{$db_prefix}peers` " .
            '(info_hash, peer_id, compact, ip, port, state, updated) ' .
            "VALUES ('{$info_hash}', '{$peer_id}', '{$compact}', " .
            "'{$ip}', {$port}, {$seeding}, " . time() . ')'
        );
    }

    public static function update_peer()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $peer_id = $db->escape($_GET['peer_id']);
        $ip = $db->escape($_GET['ip']);
        $port = (int)$_GET['port'];
        $seeding = (int)$tracker_conf['seeding'];
        $compact = $db->escape(pack('Nn', ip2long($_GET['ip']), $port));

        $db->query(
            "UPDATE `{$db_prefix}peers` " .
            "SET compact='{$compact}', ip='{$ip}', port={$port}, " .
            "state={$seeding}, updated=" . time() .
            " WHERE info_hash='{$info_hash}' AND peer_id='{$peer_id}'"
        );
    }

    public static function update_last_access()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $peer_id = $db->escape($_GET['peer_id']);

        $db->query(
            "UPDATE `{$db_prefix}peers` SET updated=" . time() .
            " WHERE info_hash='{$info_hash}' AND peer_id='{$peer_id}'"
        );
    }

    public static function delete_peer()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $peer_id = $db->escape($_GET['peer_id']);

        $db->query(
            "DELETE FROM `{$db_prefix}peers` " .
            "WHERE info_hash='{$info_hash}' AND peer_id='{$peer_id}'"
        );
    }

    public static function event()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $peer_id = $db->escape($_GET['peer_id']);

        $pState = $db->get_row(
            "SELECT ip, port, state FROM `{$db_prefix}peers` " .
            "WHERE info_hash='{$info_hash}' AND peer_id='{$peer_id}'",
            'ARRAY_N'
        );

        switch ((isset($_GET['event']) ? $_GET['event'] : false))
        {
            case 'stopped':
                if (isset($pState[2])) self::delete_peer();
                break;
            case 'completed':
                $GLOBALS['_SERVER']['tracker']['seeding'] = 1;
            case 'started':
            default:
                if (!isset($pState[2])) self::new_peer();
                elseif (
                    $pState[0] != $_GET['ip'] ||
                    ($pState[1]+0) != $_GET['port'] ||
                    ($pState[2]+0) != $GLOBALS['_SERVER']['tracker']['seeding']
                ) self::update_peer();
                else self::update_last_access();
        }
    }

    public static function peers()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $info_hash = $db->escape($_GET['info_hash']);
        $numwant = (int)$_GET['numwant'];

        $total = $db->get_var(
            "SELECT COUNT(*) FROM `{$db_prefix}peers` WHERE info_hash='{$info_hash}'"
        );

        $sql = 'SELECT ' .
            ($_GET['compact'] ? 'compact ' :
            (!$_GET['no_peer_id'] ? 'peer_id, ' : '') .
            'ip, port '
            ) .
            "FROM `{$db_prefix}peers` WHERE info_hash='{$info_hash}'" .
            ($total <= $numwant ? ';' :
                ($total <= $tracker_conf['random_limit'] ?
                    " ORDER BY RAND() LIMIT {$numwant};" :
                    " LIMIT {$numwant} OFFSET " .
                    mt_rand(0, ($total-$numwant))
                )
            );

        $response = 'd8:intervali' . $tracker_conf['announce_interval'] .
                    'e12:min intervali' . $tracker_conf['min_interval'] .
                    'e5:peers';

        if ($_GET['compact'])
        {
            $peers = '';
            $results = $db->get_results($sql, 'ARRAY_N');
            if ($results) {
                foreach ($results as $peer) {
                    $peers .= $peer[0];
                }
            }
            $response .= strlen($peers) . ':' . $peers;
        }
        else
        {
            $response .= 'l';
            $results = $db->get_results($sql, 'ARRAY_N');
            if ($results) {
                foreach ($results as $peer) {
                    // if no_peer_id is set (1), then peer_id is omitted.
                    // cols: ip (0), port (1) if no_peer_id
                    // cols: peer_id (0), ip (1), port (2) if peer_id included

                    if (!$_GET['no_peer_id']) {
                        $response .= 'd2:ip' . strlen($peer[1]) . ":{$peer[1]}" . "7:peer id20:{$peer[0]}4:porti{$peer[2]}ee";
                    } else {
                        $response .= 'd2:ip' . strlen($peer[0]) . ":{$peer[0]}4:porti{$peer[1]}ee";
                    }
                }
            }
            $response .= 'e';
        }

        echo $response . 'e';
    }

    public static function updateUser()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];

        if ($tracker_conf['open_tracker'] === 'false') {
            if (!isset($_GET['pid']) || strlen($_GET['pid']) != 32)
                tracker_error('This is private tracker! Use your "PID" for download this torrent');
            else {
                $pid = $db->escape($_GET['pid']);
                $user = $db->get_row("SELECT id FROM users WHERE private_id = '{$pid}' LIMIT 1");
                if ($user) {
                    $up = (int)($_GET['uploaded'] ?? 0);
                    $down = (int)($_GET['downloaded'] ?? 0);

                    // IFNULL is standard SQL, should work
                    $db->query("UPDATE users SET
                                upload=IFNULL(upload,0)+ {$up},
                                download=IFNULL(download,0)+ {$down}
                                WHERE id = '{$user->id}'");
                } else
                    tracker_error('You are not authorized to download this torrent!');
            }
        }
    }

    public static function scrape()
    {
        $db = DB::getInstance();
        $tracker_conf = $GLOBALS['_SERVER']['tracker'];
        $db_prefix = $tracker_conf['db_prefix'] ?? '';

        $response = 'd5:filesd';

        // scrape info_hash
        if (isset($_GET['info_hash']))
        {
            $info_hash = $db->escape($_GET['info_hash']);

            $scrape = $db->get_row(
                "SELECT SUM(state=1), SUM(state=0) " .
                "FROM `{$db_prefix}peers` " .
                "WHERE info_hash='{$info_hash}'",
                'ARRAY_N'
            );

            if (!$scrape) tracker_error('unable to scrape the requested torrent');

            $response .= "20:{$_GET['info_hash']}d8:completei" . ((int)$scrape[0]) .
                         'e10:downloadedi0e10:incompletei' . ((int)$scrape[1]) . 'ee';
        }
        // full scrape
        else
        {
            $sql = 'SELECT ' .
                'info_hash, SUM(state=1), SUM(state=0) ' .
                "FROM `{$db_prefix}peers` " .
                'GROUP BY info_hash';

            $results = $db->get_results($sql, 'ARRAY_N');
            if ($results) {
                foreach ($results as $scrape) {
                     $response .= "20:{$scrape[0]}d8:completei" . ((int)$scrape[1]) .
                         'e10:downloadedi0e10:incompletei' . ((int)$scrape[2]) . 'ee';
                }
            }
        }

        echo $response . 'ee';
    }
}
