@props(['disabled' => false])

<input 
    @disabled($disabled) 
    {{ $attributes->merge([
        'class' => '
            border-gray-300 
            focus:border-indigo-500 
            focus:ring-indigo-500 
            hover:border-indigo-400 
            hover:ring-indigo-400 
            rounded-lg 
            shadow-sm 
            transition-all 
            duration-200 
            ease-in-out 
            placeholder-gray-400 
            text-gray-700 
            focus:outline-none 
            px-4 
            py-3 
            text-base
        ']) 
    }} 
>


