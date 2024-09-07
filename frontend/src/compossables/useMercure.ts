import { ref, onMounted, onUnmounted, Ref } from 'vue';
import { MercureService } from '@/services/MercureService';

interface MercureOptions<T> {
  topic: string;
  onMessage: (data: T) => void;
  onError?: (error: Event) => void;
}

export function useMercure<T>({ topic, onMessage, onError }: MercureOptions<T>) {
  const mercureService = new MercureService(import.meta.env.VITE_APP_MERCURE_HUB_URL);
  const isConnected: Ref<boolean> = ref(false);

  const subscribe = async () => {
    try {
      await mercureService.subscribe(topic, (data: T) => {
        isConnected.value = true;
        onMessage(data);
      });
    } catch (error) {
      console.error('Failed to subscribe to Mercure:', error);
      if (onError) {
        onError(error as Event);
      }
    }
  };

  const unsubscribe = () => {
    mercureService.unsubscribe();
    isConnected.value = false;
  };

  onMounted(() => {
    subscribe();
  });

  onUnmounted(() => {
    unsubscribe();
  });

  return {
    isConnected,
    subscribe,
    unsubscribe,
  };
}