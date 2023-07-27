<script>
    import { page } from '@inertiajs/svelte'
</script>

<dl>
    <dt>Year</dt>
    <dd>{$page.props.year}</dd>

    <dt>Month</dt>
    <dd>{$page.props.month}</dd>

    <dt>Day</dt>
    <dd>{$page.props.day}</dd>

    <dt>Slug</dt>
    <dd>{$page.props.slug}</dd>
  </dl>
