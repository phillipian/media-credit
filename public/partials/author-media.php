<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package phillipian/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

?>
<div class="media-archive">
    <?php
    if (count($media) > 0) :
    foreach ( $media as $attachment ) : ?>
        <?php
        \setup_postdata( $attachment );

        // If media is attached to a post, link to the parent post. Otherwise, link to attachment page itself.
        ?>
        <div class='media-archive-item' id='attachment-<?php echo \esc_attr( $attachment->ID ); ?>'>
            <?php if ( $attachment->post_parent > 0 ) : ?>
                <a href="<?php \the_permalink( $attachment->post_parent ); ?>" title="<?php \the_title_attribute( $attachment->post_parent ); ?>"><?php echo \wp_get_attachment_image( $attachment->ID, 'large' ); ?></a>
            <?php elseif ( $link_without_parent ) : ?>
                <?php echo \wp_get_attachment_image( $attachment->ID, 'large' ); ?>
            <?php else : ?>
                <?php echo \wp_get_attachment_link( $attachment->ID, 'large', true ); ?>
            <?php endif; ?>
        </div>
    <?php
    endforeach; ?>
        <script>
            var $grid = $(".media-archive").masonry({
                itemSelector: '.media-archive-item',
                percentPosition: true,
                gutter: 24,
                columnWidth: '.media-archive-item'
            });
            $grid.imagesLoaded(function() {
                $grid.masonry();
            });
        </script>

    <?php endif; ?>
</div>
