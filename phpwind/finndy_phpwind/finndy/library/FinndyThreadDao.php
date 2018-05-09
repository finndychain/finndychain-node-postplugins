<?php
Wind::import('LIB:base.PwBaseDao');

class FinndyThreadDao extends PwBaseDao {

	protected $_table = 'bbs_threads';
	protected $_pk = 'tid';
	protected $_dataStruct = array('tid', 'fid', 'topic_type', 'subject', 'topped', 'locked', 'digest','overtime', 'highlight', 'disabled', 'ischeck', 'replies', 'hits', 'special', 'created_time', 'created_username', 'created_userid', 'created_ip', 'modified_time', 'modified_username', 'modified_userid', 'modified_ip', 'lastpost_time', 'lastpost_userid', 'lastpost_username', 'reply_notice', 'special_sort');

        public function updateThread($tid, $data) {
            $result = $this->_update($tid, $data);
        }

}