<?php
// #Metrics: {"complexity": 4, "nrMethods": 2, "nrClasses": 1}
class BinaryTree
{
    // #LineComplexity: 2
    public function insert($item)
    {
        $node = new BinaryNode($item);
        if ($this->isEmpty()) {
            // special case if tree is empty
            $this->root = $node;
        } else {
            // insert the node somewhere in the tree starting at the root
            $this->insertNode($node, $this->root);
        }
    }
    // #LineComplexity: 4
    protected function insertNode($node, &$subtree)
    {
        if ($subtree === null) {
            // insert node here if subtree is empty
            $subtree = $node;
        } else {
            if ($node->value > $subtree->value) {
                // keep trying to insert right
                $this->insertNode($node, $subtree->right);
            } else if ($node->value < $subtree->value) {
                // keep trying to insert left
                $this->insertNode($node, $subtree->left);
            } else {
                // reject duplicates
            }
        }
    }
}
