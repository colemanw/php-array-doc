<?php

namespace PhpArrayDocument;

trait CommentableTrait {

  /**
   * @var string[]
   *   Ex: ["// First line\n", "// Second line\n"]
   */
  public $comments = NULL;

  /**
   * Get a clean version of the comment, without any comment-markers.
   *
   * @return string|null
   */
  public function getCleanComment(): ?string {
    return CommentUtil::toCleanComments($this->comments);
  }

  /**
   * Set the clean version of the comment. (Comment markers will be added automatically.)
   *
   * @param string|null $comment
   */
  public function setCleanComment(?string $comment): void {
    $this->comments = CommentUtil::toRawComments($comment);
  }

  /**
   * Get the raw comment code, including the comment markers.
   *
   * @param string $prefix
   * @return string|null
   */
  public function getIndentedComments(string $prefix = ''): ?string {
    return CommentUtil::toIndentedComments($prefix, $this->comments);
  }

}
