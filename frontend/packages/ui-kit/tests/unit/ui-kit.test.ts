import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { UiButton, UiInput, UiModalShell, UiRecipeCard } from '../../src';

describe('ui-kit components', () => {
  it('renders button slot and variant class', () => {
    const wrapper = mount(UiButton, {
      props: { variant: 'secondary' },
      slots: { default: 'Click' },
    });
    expect(wrapper.text()).toContain('Click');
    expect(wrapper.classes()).toContain('secondary');
  });

  it('renders input label and updates value', async () => {
    const wrapper = mount(UiInput, {
      props: { label: 'Email', modelValue: '' },
    });
    expect(wrapper.text()).toContain('Email');
    await wrapper.find('input').setValue('user@example.com');
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('user@example.com');
  });

  it('renders modal shell when open', () => {
    const wrapper = mount(UiModalShell, {
      props: { title: 'My Modal', open: true },
      slots: {
        default: '<p>Body</p>',
        actions: '<button>OK</button>',
      },
    });
    expect(wrapper.text()).toContain('My Modal');
    expect(wrapper.text()).toContain('Body');
    expect(wrapper.text()).toContain('OK');
  });

  it('renders recipe card with meta and badge', () => {
    const wrapper = mount(UiRecipeCard, {
      props: { title: 'Суп', meta: '30 мин', badge: 'Обед' },
    });
    expect(wrapper.text()).toContain('Суп');
    expect(wrapper.text()).toContain('30 мин');
    expect(wrapper.text()).toContain('Обед');
  });
});
