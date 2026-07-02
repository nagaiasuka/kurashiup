<form
    role="search"
    method="get"
    action="<?php echo esc_url(home_url('/')); ?>"
    class="relative"
>

    <input
        type="hidden"
        name="post_type"
        value="product"
    >

    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-white/50"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
        />
    </svg>

    <input
        type="search"
        name="s"
        value="<?php echo esc_attr(get_search_query()); ?>"
        placeholder="Search products..."
        class="w-64 rounded-full border border-white/10 bg-white/5 py-3 pl-12 pr-5 text-sm text-white placeholder:text-white/40 backdrop-blur-xl transition duration-300 focus:border-[#C6A46A] focus:bg-white/10 focus:outline-none"
    >

</form>