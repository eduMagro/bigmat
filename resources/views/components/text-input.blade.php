@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => '
            border-gray-300 dark:border-gray-600
            bg-white dark:bg-gray-700
            focus:border-indigo-500 dark:focus:border-indigo-400
            focus:ring-indigo-500 dark:focus:ring-indigo-400
            hover:border-indigo-400
            hover:ring-indigo-400
            rounded-lg
            shadow-sm
            transition-all
            duration-200
            ease-in-out
            placeholder-gray-400 dark:placeholder-gray-500
            text-gray-700 dark:text-gray-200
            focus:outline-none
            px-4
            py-3
            text-base
        '])
    }}
>


