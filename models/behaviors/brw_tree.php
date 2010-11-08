<?php
/**
 * This behavior should be removed when the following ticket will be approved
 * http://cakephp.lighthouseapp.com/projects/42648/tickets/1263-complex-sort-on-tree-reorder
 */

class BrwTreeBehavior extends TreeBehavior {


/**
 * Reorder method copied and modified from core.
 *
 * Reorders the nodes (and child nodes) of the tree according to the field and direction specified in the parameters.
 * This method does not change the parent of any node.
 *
 * Requires a valid tree, by default it verifies the tree before beginning.
 *
 * Options:
 *
 * - 'id' id of record to use as top node for reordering
 * - 'field' Which field to use in reordeing defaults to displayField
 * - 'order' Direction to order either DESC or ASC (defaults to ASC)
 * - 'verify' Whether or not to verify the tree before reorder. defaults to true.
 *
 * @param AppModel $Model Model instance
 * @param array $options array of options to use in reordering.
 * @return boolean true on success, false on failure
 * @link http://book.cakephp.org/view/1355/reorder
 * @link http://book.cakephp.org/view/1629/Reorder
 */
	function reorder(&$Model, $options = array()) {
		$options = array_merge(array('id' => null, 'field' => $Model->displayField, 'order' => 'ASC', 'verify' => true), $options);
		extract($options);
		if ($verify && !$this->verify($Model)) {
			return false;
		}
		$verify = false;
		extract($this->settings[$Model->alias]);
		$fields = array($Model->primaryKey, $field, $left, $right);
		$sort = (!empty($options['sort']))? $options['sort'] : $field . ' ' . $order;
		$nodes = $this->children($Model, $id, true, $fields, $sort, null, null, $recursive);

		$cacheQueries = $Model->cacheQueries;
		$Model->cacheQueries = false;
		if ($nodes) {
			foreach ($nodes as $node) {
				$id = $node[$Model->alias][$Model->primaryKey];
				$this->moveDown($Model, $id, true);
				if ($node[$Model->alias][$left] != $node[$Model->alias][$right] - 1) {
					$this->reorder($Model, compact('id', 'field', 'order', 'verify'));
				}
			}
		}
		$Model->cacheQueries = $cacheQueries;
		return true;
	}


}

