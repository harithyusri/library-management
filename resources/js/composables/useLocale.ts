import { ref, computed } from 'vue';

type Locale = 'ms' | 'en';

const locale = ref<Locale>((localStorage.getItem('locale') as Locale) ?? 'ms');

export function useLocale() {
    const toggle = () => {
        locale.value = locale.value === 'ms' ? 'en' : 'ms';
        localStorage.setItem('locale', locale.value);
    };

    const t = (strings: { ms: string; en: string }) => computed(() => strings[locale.value]);

    return { locale, toggle, t };
}
