<h2>Package Info</h2>

<ul>
    <li><strong>Package:</strong> <?php print $package_name; ?></li>
    <li><strong>Description:</strong> <?php print $description; ?></li>
    <li><strong>Homepage:</strong> <a href="<?php print $homepage; ?>"><?php print $homepage; ?></a></li>
    <li><strong>License:</strong> <?php print $license; ?></li>
</ul>

<h3>Authors</h3>
<ul>
<?php foreach ($authors as $a): ?>
    <li>
        <a href="<?php print $a['homepage']; ?>"><?php print $a['name']; ?></a> 
        (<a href="mailto:<?php print $a['email']; ?>"><?php print $a['email']; ?></a>)
    </li>
<?php endforeach; ?>
</ul>

<h3>Support</h3>

<ul>
    <li><strong>Email:</strong> <a href="mailto:<?php print $support['email']; ?>"><?php print $support['email']; ?></a></li>
    <li><strong>Issues:</strong> <a href="<?php print $support['issues']; ?>"><?php print $support['issues']; ?></a></li>
    <li><strong>Forum:</strong> <a href="<?php print $support['forum']; ?>"><?php print $support['forum']; ?></a></li>
    <li><strong>Wiki:</strong> <a href="<?php print $support['wiki']; ?>"><?php print $support['wiki']; ?></a></li>
    <li><strong>Source:</strong> <a href="<?php print $support['source']; ?>"><?php print $support['source']; ?></a></li>
</ul>