<?php

add_shortcode('wp-instagram-connect', 'instagramShortcode');

function instagramShortcode()
{
    if (function_exists('fetchInstagramPosts')) {
        $instagram = fetchInstagramPosts();
        if ($instagram):
            ?>

            <section class="instagram-feed">
                <div class="container">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="-0.422 -0.422 20 20">
                            <path fill="#575756"
                                  d="M17.921 6.099c.025.698.038 1.858.038 3.479s-.019 2.787-.056 3.498c-.038.711-.145 1.328-.318 1.853a4.36 4.36 0 0 1-1.029 1.627 4.362 4.362 0 0 1-1.627 1.03c-.523.174-1.142.279-1.853.318-.71.037-1.877.055-3.498.055s-2.788-.018-3.498-.055c-.711-.039-1.329-.156-1.853-.356a3.987 3.987 0 0 1-1.627-.992 4.347 4.347 0 0 1-1.029-1.627c-.174-.524-.28-1.142-.318-1.853-.037-.711-.056-1.877-.056-3.498s.019-2.787.056-3.498c.038-.711.145-1.328.318-1.853A4.36 4.36 0 0 1 2.6 2.6a4.347 4.347 0 0 1 1.627-1.029c.523-.175 1.142-.28 1.853-.318.71-.037 1.877-.056 3.498-.056s2.788.019 3.498.056c.711.038 1.329.144 1.853.318A4.36 4.36 0 0 1 16.556 2.6a4.347 4.347 0 0 1 1.029 1.627c.174.525.287 1.148.336 1.872zm-1.795 8.418c.149-.424.249-1.098.299-2.021.025-.548.037-1.321.037-2.319V8.979c0-1.022-.012-1.796-.037-2.319-.05-.948-.149-1.622-.299-2.021a2.703 2.703 0 0 0-1.609-1.609c-.398-.149-1.072-.249-2.02-.299a54.64 54.64 0 0 0-2.32-.037H8.979c-.998 0-1.771.012-2.32.037-.922.05-1.596.15-2.019.299-.774.3-1.31.836-1.61 1.61-.149.398-.249 1.072-.299 2.02a54.588 54.588 0 0 0-.037 2.319v1.197c0 .998.012 1.771.037 2.319.05.923.149 1.597.299 2.021.324.773.861 1.31 1.609 1.609.424.149 1.098.249 2.02.299.549.025 1.322.038 2.32.038h1.197c1.022 0 1.796-.013 2.32-.038.947-.05 1.621-.149 2.02-.299.774-.323 1.31-.859 1.61-1.608zM9.578 5.275c.773 0 1.49.193 2.151.58s1.185.91 1.571 1.571.58 1.378.58 2.151-.193 1.49-.58 2.151-.91 1.185-1.571 1.571-1.378.58-2.151.58-1.49-.193-2.151-.58-1.185-.91-1.571-1.571-.58-1.378-.58-2.151.193-1.49.58-2.151.91-1.185 1.571-1.571 1.378-.58 2.151-.58zm0 7.109c.773 0 1.435-.273 1.983-.822s.822-1.21.822-1.983-.273-1.435-.822-1.983c-.549-.548-1.21-.822-1.983-.822s-1.434.273-1.983.821c-.549.549-.822 1.21-.822 1.983s.273 1.435.822 1.983 1.21.823 1.983.823zm5.5-7.295a.97.97 0 0 0-.3-.711.968.968 0 0 0-.711-.3c-.273 0-.511.1-.711.3a.973.973 0 0 0-.299.711c0 .273.1.511.299.71.2.2.438.3.711.3a.91.91 0 0 0 .693-.3 1.16 1.16 0 0 0 .318-.71z"></path>
                        </svg> <?php if(class_exists('ACF')): the_field('instagram_title', 'options'); else: echo get_option('instagram_title'); endif; ?></h3>
                </div>
                <div class="instagram-feed__photos">
                    <?php foreach (json_decode($instagram) as $post): ?>
                        <?php foreach ($post as $item) { ?>
                                <?php if($item->permalink != ''):?>
                                    <a href="<?php echo $item->permalink ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?php echo $item->media_url ?>" class="instagram-fluid">
                                    </a>
                                <?php endif; ?>
                        <?php } ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <p>Feed from Instagram is unavailable</p>
        <?php endif;
    }
}

?>