<div class="p-4 bg-white shadow rounded-lg">
         <div class="flex items-center">
             <div class="mr-4 text-{{ $color ?? 'gray' }}-500">
                 @isset($icon)
                     <x-dynamic-component :component="$icon" class="w-8 h-8" />
                 @endisset
             </div>
             <div>
                 <h3 class="text-lg font-semibold">{{ $value }}</h3>
                 <p class="text-sm text-gray-500">{{ $label }}</p>
             </div>
         </div>
     </div>